<?php

namespace Drupal\group_exclusive\Plugin\DevelGenerate;

use Drupal\devel_generate\DevelGenerateBase;
use Drupal\group\GroupMembershipLoaderInterface;
use Drupal\group\Entity\GroupContent;
use Drupal\group\Entity\GroupContentType;
use Drupal\Core\Entity\Query\QueryFactory;
use Drupal\Core\Routing\UrlGeneratorInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a GroupDevelGenerate plugin.
 * @todo support subgroups i.e. Content goes in a subgroup only if the owner is
 * a member of the parent.
 *
 * @DevelGenerate(
 *   id = "group_content",
 *   label = @Translation("group content"),
 *   description = @Translation("Put existing content into groups"),
 *   url = "group_content",
 *   permission = "administer devel_generate",
 *   settings = {
 *     "kill" = FALSE
 *   }
 * )
 */
class GroupContentDevelGenerate extends DevelGenerateBase implements ContainerFactoryPluginInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The url generator
   *
   * @var \Drupal\Core\Routing\UrlGeneratorInterface
   */
  protected $urlGenerator;

  /**
   * The Group Membership Loader service
   *
   * @var \Drupal\group\GroupMembershipLoaderInterface
   */
  protected $membershipLoader;

  /**
   * The EntityQuery Factory Object
   *
   * @var not sure
   */
  protected $entityQuery;

  /**
   * Class constructor.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin ID for the plugin instance.
   * @param array $plugin_definition
   *   The plugin implementation definition.
   * @param EntityTypeManagerInterface $entity_type_manager
   *   The Entity type manager service
   * @param UrlGeneratorInterface $url_generator
   *   The Entity type manager service
   * @param GroupMembershipLoaderInterface $membership_loader
   *   The Group Membership Loader service
   * @param QueryFactory $membership_loader
   *   The Group Membership Loader service
   */
  public function __construct(array $configuration, $plugin_id, array $plugin_definition, EntityTypeManagerInterface $entity_type_manager, UrlGeneratorInterface $url_generator, GroupMembershipLoaderInterface $membership_loader, QueryFactory $entity_query) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityTypeManager = $entity_type_manager;
    $this->urlGenerator = $url_generator;
    $this->membershipLoader = $membership_loader;
    $this->entityQuery = $entity_query;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration, $plugin_id, $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('url_generator'),
      $container->get('group.membership_loader'),
      $container->get('entity.query')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $types_count = $this->countContentOfTypes();
    if ($types_count) {
      $sentences[] = $this->t('This generator takes existing ContentEntities and puts them in existing groups.');
      $sentences[] = $this->t("It creates a new 'membership' entity for each item in each group.");
      $sentences[] = $this->t("If content has an owner, it will only be placed in groups of which the owner is a member.");
      $form['intro'] = [
        '#markup' => implode(' ', $sentences),
        '#weight' => -1
      ];
    }
    else {
      $create_url = $this->urlGenerator->generateFromRoute('entity.group_type.add_form');
      $this->setMessage($this->t(
        'You do not have any group content types because you do not have any group types. <a href=":create-type">Go create a new group type</a>',
        [':create-type' => $create_url]
      ), 'error', FALSE);
      return;
    }

    $options = [];

    $form['content_type'] = [
      '#title' => $this->t('Group content type'),
      '#type' => 'radios',
      '#options' => [],
      '#weight' => 1
    ];
    // Get the number of existing items for each plugin, and disable the
    // checkboxes with none
    foreach (GroupContentType::loadMultiple() as $id => $groupContentType) {
      $form['content_type']['#options'][$id] = $groupContentType->label();
      list($plugin, $entity_type_id, $entity_type, $bundle) = $this->parsePlugin($groupContentType);
      $quant = count($types_count[$id]);
      if ($bundle) {
        $bundle_label = \Drupal::service('entity_type.bundle.info')->getBundleInfo($entity_type_id)[$bundle]['label'];
        $summary[$bundle] = $bundle_label .':'.$quant;
      }
      else {
        $summary[$entity_type_id] = $entity_type->getLabel() .':'.$quant;
      }
      $form['content_type']['#options'][$id] .= ' ('.count($content_ids).')';
      $form['content_type'][$id]['#disabled'] = empty($types_count[$id]);
    }

    $form['content_type']['#description'] = implode('; ', $summary);

    $form['kill'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Before generating, first delete all group content of these types.'),
      '#default_value' => $this->getSetting('kill'),
      '#weight' => 2
    ];
    $form['batch'] = [
      '#type' => 'value',
      '#value' => TRUE
    ];

    $form['#redirect'] = FALSE;

    return $form;
  }

  private function countContentOfTypes() {
    foreach (GroupContentType::loadMultiple() as $id => $groupContentType) {
      // Check if any of this content actually exists.
      list($plugin, $entity_type_id, $entity_type, $bundle) = $this->parsePlugin($groupContentType);
      $query = $this->entityQuery->get($entity_type_id);
      if ($bundle) {
        $query->condition($entity_type->getKey('bundle'), $bundle);
      }
      $content_ids[$id] = $query->execute();
    }
    return $content_ids;
  }

  private function parsePlugin($groupContentType) {
    $plugin = $groupContentType->getContentPlugin();
    $entity_type_id = $plugin->getEntitytypeId();
    return [
      $plugin,
      $entity_type_id,
      \Drupal::entityTypeManager()->getDefinition($entity_type_id),
      $plugin->getEntityBundle()
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function generateElements(array $values) {
    drupal_set_message("Using GroupContentDevelGenerate from group_exlusive module, because not yet committed to group module", 'warning', FALSE);
    if ($values['kill']) {
      // @todo load and delete all groupContent with the
      $ids = $this->entityQuery->get('group_content')->condition('type', $values['content_type'])->execute();
      foreach (GroupContent::loadMultiple($ids) as $gc) {
        $gc->delete();
      }
    }
    if (empty($values['batch'])) {
      $this->addGroupContent($values);
    }
    else {
      // Start the batch.
      $batch = [
        'title' => $this->t('Generating group content'),
        'file' => drupal_get_path('module', 'devel_generate') . '/devel_generate.batch.inc',
        'operations' => [
           ['devel_generate_operation', [$this, 'batchPreGroup', $values]],
           ['devel_generate_operation', [$this, 'batchAddGroupContent', $values]]
        ]
      ];
      // Should we make a batchPostGroup?
      batch_set($batch);
    }
  }

  /**
   * The method responsible for creating groups.
   *
   * @param array $values
   *   The input values from the settings form.
   * @param array $context
   *   An array of contextual key/value information for rebuild batch process.
   */
  public function batchPreGroup($values, &$context) {
    $context['results'] = $values;
    $context['results']['num'] = 0;
  }

  /**
   * Delete existing groupContent of the given types.
   *
   * @param array $values
   *   The input values from the settings form.
   * @param array $context
   *   An array of contextual key/value information for rebuild batch process.
   */
  public function batchGroupContentKill($values, &$context) {
    $contentType = GroupContentType::load($vars['content_type']);
    $content_items = GroupContent::loadByContentPluginId($contentType->id());
    $this->groupContentKill($vars);
  }

  /**
   * Delete existing groupContent of the given type.
   *
   * @param array $values
   *   The input values from the settings form or batch
   */
  public function GroupContentKill($values) {
    foreach (GroupContent::loadByContentPluginId($values['content_type']) as $item) {
      $item->delete();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function validateDrushParams($args) {
    $values['content_type'] = $args['type'];
    $values['kill'] = drush_get_option('kill');
    return $values;
  }

  public function batchAddGroupContent($values) {
    $this->addGroupContent($values);
  }

  /**
   * Put all content of one type into all groups that support it.
   *
   * @param array $values
   *   The input values from the settings form with some additional data needed
   *   for the generation.
   *
   * @note This function loads many entities and could cause memory problems
   */
  public function addGroupContent($values) {
    // The GroupContentType plugin tells us which groupType(s) we need.
    $groupContentType = GroupContentType::load($values['content_type']);
    $plugin = $groupContentType->getContentPlugin();
    $entity_type_id = $plugin->getEntityTypeId();
    $group_type_id = $groupContentType->getGroupTypeId();
    // Load all the groups of this type.
    $groups = $this->entityTypeManager->getStorage('group')
      ->loadByProperties(['type' => $group_type_id]);
    if (empty($groups)) {
      \Drupal::logger('groupcontent')->warning(
        'No %group_type_id groups to which to addGroupContent %type',
        ['%group_type_id' => $group_type_id, '%type' => $values['content_type']]
      );
      return;
    }
    // Load ALL the entities of this type.
    if ($bundle_key  = $this->entityTypeManager->getDefinition($entity_type_id)->getKey('bundle')) {
      $content = $this->entityTypeManager->getStorage($entity_type_id)->loadByProperties([
        $bundle_key => $plugin->getEntityBundle()
      ]);
    }
    else {
      $content = $this->entityTypeManager->getStorage($entity_type_id)->loadMultiple();
    }

    $groups = array_values($groups);
    $plugin_id = $groupContentType->getContentPlugin()->getPluginId();

    if($plugin_id == 'group_membership') {
      // Get the roles for this group-type
      $roles = $this->entityQuery->get('group_role')
        ->condition('group_type', $group_type_id, '=')
        ->condition('internal', 0, '=')
        ->execute();
      $roles = array_values($roles);
    }

    // Loop around the groups adding one entity at a time until every entity is
    // added to one and only one group
    $i = 0;
    while ($entity = array_pop($content)) {
      $group = $groups[$i % count($groups)];
      $group->addContent(
        $entity,
        $plugin_id,
        ['uid' => $entity instanceof \Drupal\user\EntityOwnerInterface ? $entity->getOwnerId() : 1]
      );
      $i++;
    }


  }

}

