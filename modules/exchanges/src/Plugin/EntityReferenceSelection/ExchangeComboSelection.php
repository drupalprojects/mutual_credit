<?php

namespace Drupal\mcapi_exchanges\Plugin\EntityReferenceSelection;

use Drupal\group\Entity\GroupContentType;
use Drupal\group\Plugin\GroupContentEnablerManager;
use Drupal\Core\Database\Query\SelectInterface;
use Drupal\Core\Entity\Plugin\EntityReferenceSelection\DefaultSelection;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Select only things from the exchange returned by the 'combo' context
 *
 * @______EntityReferenceSelection(
 *   id = "exchange_combo",
 *   label = @Translation("Entities in exchange (from combo context)"),
 *   group = "exchange_combo",
 *   deriver = "Drupal\group\Plugin\EntityReferenceSelection\GroupSelectionDeriver",
 *   weight = 2
 * )
 *
 * @deprecated
 */
class ExchangeComboSelection extends DefaultSelection {

  /**
   * @var \Drupal\group\Plugin\GroupContentEnablerManager
   */
  private $groupContentEnabler;

  /**
   * Constructs a new SelectionBase object.
   *
   * Same as parent plus
   *
   * @param Drupal\group\Plugin\GroupContentEnablerManager $group_content_enabler
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, $entity_manager, $module_handler, $current_user, GroupContentEnablerManager $group_content_enabler) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $entity_manager, $module_handler, $current_user);
    $this->groupContentEnabler = $group_content_enabler;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity.manager'),
      $container->get('module_handler'),
      $container->get('current_user'),
      $container->get('plugin.manager.group_content_enabler')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $selection_handler_settings = $this->configuration['handler_settings'];
    $form['auto_create'] =[
      '#type' => 'checkbox',
      '#title' => $this->t("Create referenced entities if they don't already exist"),
      '#default_value' => $selection_handler_settings['auto_create'],
      '#weight' => -2,
    ];

    // @todo add sort field as in parent::buildConfigurationForm
    $plugins = [];
    foreach ($this->groupContentEnabler->getDefinitions() as $id => $def) {
      if ($def['entity_type_id'] == $this->configuration['target_type']) {
        foreach ($this->groupContentEnabler->getGroupContentTypeIds($id) as $plugin_id) {
          $plugins[$plugin_id] = GroupContentType::load($plugin_id)->label();
        }
      }
    }
    $form['target_content_types'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t(
        '@entity_type group content.',
        ['@entity_type' => \Drupal::entityTypeManager()->getDefinition($this->configuration['target_type'])->getLabel()]
      ),
      '#description' => $this->t('Group content types from the route, otherwise currentUser, otherwise content.'),
      '#options' => $plugins,
      '#default_value' => isset($selection_handler_settings['target_content_types']) ? (array)$selection_handler_settings['target_content_types'] : [],
      '#required' => TRUE,
      '#size' => 6,
      '#multiple' => TRUE,
      '#ajax' => TRUE,
      '#limit_validation_errors' => [],
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
    // If no checkboxes were checked for 'target_bundles', store NULL ("all
    // bundles are referenceable") rather than empty array ("no bundle is
    // referenceable" - typically happens when all referenceable bundles have
    // been deleted).
    if ($form_state->getValue(['settings', 'handler_settings', 'target_content_types']) === []) {
      $form_state->setValue(['settings', 'handler_settings', 'target_content_types'], NULL);
    }
  }

  /**
   * {@inheritdoc}
   *
   * @todo New entities are only valid if they are in the same group. This
   * handler should probably create the GroupContrent entity
   */
  public function validateReferenceableNewEntities(array $entities) {
    return parent::validateReferenceableNewEntities($entities); //bool
  }


  /**
   * {@inheritdoc}
   *
   * @note we might do this differently after https://www.drupal.org/node/2424791 lands
   */
  protected function buildEntityQuery($match = NULL, $match_operator = 'CONTAINS') {
    $target_type = $this->configuration['target_type'];
    //$handler_settings = $this->configuration['handler_settings'];
    $entity_type = $this->entityManager->getDefinition($target_type);

    $query = $this->entityManager->getStorage($this->configuration['target_type'])->getQuery();

    if (isset($match) && $label_key = $entity_type->getKey('label')) {
      $query->condition($label_key, $match, $match_operator);
    }

    // Add entity-access tag.
    $query->addTag($target_type . '_access');

    // Add the Selection handler for system_query_entity_reference_alter().
    $query->addTag('entity_reference');
    $query->addMetaData('entity_reference_selection_handler', $this);

    // @todo Add the sort option.
//    if (!empty($handler_settings['sort'])) {
//      $sort_settings = $handler_settings['sort'];
//      if ($sort_settings['field'] != '_none') {
//        $query->sort($sort_settings['field'], $sort_settings['direction']);
//      }
//    }

    return $query;
  }


  /**
   * {@inheritdoc}
   *
   * We do this in entityQueryAlter because EntityQuery object cannot join tables in Core 8.2
   */
  public function entityQueryAlter(SelectInterface $query) {
    $selection_handler_settings = $this->configuration['handler_settings'];
    // Join the entityQuery to the groupContent table
    $target_key = $this->entityManager->getDefinition($this->configuration['target_type'])->getKey('id');
    $group_content_data_table =  $this->entityManager->getDefinition('group_content')->getDataTable();

    $group_content_entity_type = $this->entityManager->getDefinition('group_content');
    $query->innerJoin(
      $group_content_data_table,
      $group_content_data_table,
      "base_table.{$target_key} = {$group_content_data_table}.id AND group_content_field_data.default_langcode = 1"
    );
    // Filter by the groupcontenttypes
    if (isset($selection_handler_settings['target_content_types'])) {
      // If 'target_bundles' is an empty array, no bundle is referenceable,
      // force the query to never return anything and bail out early.
      if (!empty($handler_settings['target_content_types'])) {
        $query->condition($group_content_data_table.'.type', array_filter($selection_handler_settings['target_content_types']));
      }
    }
    // Filter by the groups from the context
    if ($mem = group_exclusive_membership_get('exchange')) {
      $query->condition($group_content_data_table.'.gid', $mem->getGroup()->id());
    }
  }

}
