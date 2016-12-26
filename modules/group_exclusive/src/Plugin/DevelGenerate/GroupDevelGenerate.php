<?php

namespace Drupal\group_exclusive\Plugin\DevelGenerate;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Routing\UrlGeneratorInterface;
use Drupal\devel_generate\DevelGenerateBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a GroupDevelGenerate plugin.
 *
 * @DevelGenerate(
 *   id = "group",
 *   label = @Translation("groups"),
 *   description = @Translation("Generate a given number of groups. Optionally delete current groups."),
 *   url = "group",
 *   permission = "administer devel_generate",
 *   settings = {
 *     "num" = 50,
 *     "kill" = FALSE,
 *     "label_length" = 4,
 *   }
 * )
 */
class GroupDevelGenerate extends DevelGenerateBase implements ContainerFactoryPluginInterface {

  /**
   * The entity manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The language manager.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * The url generator service.
   *
   * @var \Drupal\Core\Routing\UrlGeneratorInterface
   */
  protected $urlGenerator;

  /**
   * The date formatter service.
   *
   * @var \Drupal\Core\Datetime\DateFormatterInterface
   */
  protected $dateFormatter;

  /**
   * Class constructor.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin ID for the plugin instance.
   * @param array $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager.
   * @param \Drupal\Core\Routing\UrlGeneratorInterface $url_generator
   *   The url generator service.
   * @param \Drupal\Core\Datetime\DateFormatterInterface $date_formatter
   *   The date formatter service.
   */
  public function __construct(array $configuration, $plugin_id, array $plugin_definition, EntityTypeManagerInterface $entity_type_manager, ModuleHandlerInterface $module_handler, LanguageManagerInterface $language_manager, UrlGeneratorInterface $url_generator, DateFormatterInterface $date_formatter) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->entityTypeManager = $entity_type_manager;
    $this->moduleHandler = $module_handler;
    $this->languageManager = $language_manager;
    $this->urlGenerator = $url_generator;
    $this->dateFormatter = $date_formatter;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration, $plugin_id, $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('module_handler'),
      $container->get('language_manager'),
      $container->get('url_generator'),
      $container->get('date.formatter')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $types = $this->entityTypeManager->getStorage('group_type')->loadMultiple();

    if (empty($types)) {
      $create_url = $this->urlGenerator->generateFromRoute('entity.group_type.add_form');
      $this->setMessage($this->t(
        'You do not have any group types that can be generated. <a href=":create-type">Go create a new group type</a>',
        [':create-type' => $create_url]
      ), 'error', FALSE);
      return;
    }

    $options = [];
    foreach ($types as $type) {
      $options[$type->id()] = $type->label();
    }

    $form['group_types'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Group type'),
      '#options' => $options,
    ];

    $form['kill'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('<strong>Delete all group</strong> in these group types before generating new group.'),
      '#default_value' => $this->getSetting('kill'),
    ];

    $form['num'] = [
      '#type' => 'number',
      '#title' => $this->t('How many groups would you like to generate?'),
      '#default_value' => $this->getSetting('num'),
      '#required' => TRUE,
      '#min' => 0,
    ];

    $options = [1 => $this->t('Now')];
    foreach ([3600, 86400, 604800, 2592000, 31536000] as $interval) {
      $options[$interval] = $this->dateFormatter->formatInterval($interval, 1) . ' ' . $this->t('ago');
    }

    $form['time_range'] = [
      '#type' => 'select',
      '#title' => $this->t('How far back in time should the groups be dated?'),
      '#description' => $this->t('Group creation dates will be distributed randomly from the current time, back to the selected time.'),
      '#options' => $options,
      '#default_value' => 604800,
    ];

    //added by matslats
    $account = \Drupal::currentUser();
    $form['uid'] = [
      '#title' => $this->t('Creator'),
      '#description' => $this->t('The creator is always the first member of the group.'),
      '#type' => 'radios',
      '#options' => [
        0 => $this->t('Random users'),
        $account->id() => \Drupal::currentUser()->getDisplayname()
      ],
      '#required' => TRUE
    ];

    $form['label_length'] = [
      '#type' => 'number',
      '#title' => $this->t('Maximum number of words in titles'),
      '#default_value' => $this->getSetting('label_length'),
      '#required' => TRUE,
      '#min' => 1,
      '#max' => 255,
    ];
    $form['add_alias'] = [
      '#type' => 'checkbox',
      '#disabled' => !$this->moduleHandler->moduleExists('path'),
      '#description' => $this->t('Requires path.module'),
      '#title' => $this->t('Add an url alias for each groups.'),
      '#default_value' => FALSE,
    ];

    $options = [];
    $languages = $this->languageManager->getLanguages(LanguageInterface::STATE_ALL);
    foreach ($languages as $langcode => $language) {
      $options[$langcode] = $language->getName();
    }

    $form['add_language'] = [
      '#type' => 'select',
      '#title' => $this->t('Set language on groups'),
      '#multiple' => TRUE,
      '#description' => $this->t('Requires locale.module'),
      '#options' => $options,
      '#default_value' => [
        $this->languageManager->getDefaultLanguage()->getId(),
      ],
    ];

    $form['#redirect'] = FALSE;

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  protected function generateElements(array $values) {
    drupal_set_message("Using GroupDevelGenerate from group_exlusive module, because not yet committed to group module", 'warning', FALSE);
    if ($values['num'] <= 50) {
      $this->generateGroups($values);
    }
    else {
      $this->generateBatchGroups($values);
    }
  }

  /**
   * Method responsible for creating groups (non-batch).
   *
   * @param array $values
   *   The input values from the settings form.
   */
  private function generateGroups($values) {
    $values['group_types'] = array_filter($values['group_types']);
    if (!empty($values['kill']) && $values['group_types']) {
      $this->groupsKill($values);
    }

    if (!empty($values['group_types'])) {
      // Generate groups.
      $this->preGroup($values);
      $start = time();
      for ($i = 1; $i <= $values['num']; $i++) {
        $this->addGroup($values);
        if (function_exists('drush_log') && $i % drush_get_option('feedback', 1000) == 0) {
          $now = time();
          drush_log(dt('Completed @feedback groups (@rate groups/min)', [
            '@feedback' => drush_get_option('feedback', 1000),
            '@rate' => (drush_get_option('feedback', 1000) * 60) / ($now - $start),
          ]), 'ok');
          $start = $now;
        }
      }
    }
    $this->setMessage($this->formatPlural($values['num'], '1 group created.', 'Finished creating @count groups'));
  }

  /**
   * Method responsible for creating groups (batch).
   *
   * @param array $values
   *   The input values from the settings form.
   */
  private function generateBatchGroups($values) {
    // Setup the batch operations and save the variables.
    $operations[] = [
      'devel_generate_operation',
      [$this, 'batchPreGroup', $values],
    ];

    // Add the kill operation.
    if ($values['kill']) {
      $operations[] = [
        'devel_generate_operation',
        [$this, 'batchGroupsKill', $values],
      ];
    }

    // Add the operations to create the groups.
    for ($num = 0; $num < $values['num']; $num++) {
      $operations[] = [
        'devel_generate_operation',
        [$this, 'batchAddGroup', $values],
      ];
    }

    // Start the batch.
    $batch = [
      'title' => $this->t('Generating Groups'),
      'operations' => $operations,
      'finished' => 'devel_generate_batch_finished',
      'file' => drupal_get_path('module', 'devel_generate') . '/devel_generate.batch.inc',
    ];
    batch_set($batch);
  }

  /**
   * The method responsible for creating groups.
   *
   * @param array $vars
   *   The input values from the settings form.
   * @param array $context
   *   An array of contextual key/value information for rebuild batch process.
   */
  public function batchPreGroup($vars, &$context) {
    $context['results'] = $vars;
    $context['results']['num'] = 0;
    $this->preGroup($context['results']);
  }

  /**
   * Wrapper around addGroup() for Batch processing.
   *
   * @param array $vars
   *   The input values from the settings form.
   * @param array $context
   *   An array of contextual key/value information for rebuild batch process.
   */
  public function batchAddGroup($vars, &$context) {
    $this->addGroup($context['results']);
    $context['results']['num']++;
  }

  /**
   * Wrapper around groupsKill() for Batch processing.
   *
   * @param array $vars
   *   The input values from the settings form.
   * @param array $context
   *   An array of contextual key/value information for rebuild batch process.
   */
  public function batchGroupsKill($vars, &$context) {
    $this->groupsKill($context['results']);
  }

  /**
   * {@inheritdoc}
   */
  public function validateDrushParams($args) {
    $add_language = drush_get_option('languages');
    if (!empty($add_language)) {
      $add_language = explode(',', str_replace(' ', '', $add_language));
      // Intersect with the enabled languages to make sure the language args
      // passed are actually enabled.
      $values['values']['add_language'] = array_intersect(
        $add_language,
        array_keys($this->languageManager->getLanguages(LanguageInterface::STATE_ALL))
      );
    }

    $values['kill'] = drush_get_option('kill');
    $values['label_length'] = 6;
    $values['num'] = array_shift($args);

    $selected_types = _convert_csv_to_array(drush_get_option('types'));

    $values['group_types'] = array_combine($selected_types, $selected_types);
    $group_types = array_filter($values['group_types']);

    if (!empty($values['kill']) && empty($group_types)) {
      return drush_set_error('DEVEL_GENERATE_INVALID_INPUT', dt('Please provide group type (--types) in which you want to delete the groups.'));
    }

    return $values;
  }

  /**
   * Deletes all groups of given group types.
   *
   * @param array $values
   *   The input values from the settings form.
   */
  protected function groupsKill($values) {
    /** @var \Drupal\Core\Entity\EntityStorageInterface $group_storage */
    $group_storage = $this->entityTypeManager->getStorage('group');
    $group_ids = $group_storage->getQuery()
      ->condition('type', $values['group_types'], 'IN')
      ->execute();
    if (!empty($group_ids)) {
      $groups = $group_storage->loadMultiple($group_ids);
      $group_storage->delete($groups);
      $this->setMessage($this->t('Deleted %count groups.', ['%count' => count($group_ids)]));
    }
  }

  /**
   * Adds list of user uids to $results.
   *
   * @param array &$results
   *   The input values from the settings form with some additional data needed
   *   for the generation.
   */
  protected function preGroup(&$results) {
    // Get user id.
    $users = $this->getUsers();
    $results['users'] = $users;
  }

  /**
   * Create one group. Used by both batch and non-batch code branches.
   *
   * @param array &$results
   *   The input values from the settings form with some additional data needed
   *   for the generation.
   */
  protected function addGroup(&$results) {
    if (!isset($results['time_range'])) {
      $results['time_range'] = 0;
    }
    $users = $results['users'];
    $types = array_filter($results['group_types']);
    $group_type = $types[array_rand($types)];
    $uid = $results['uid'] ?: $users[array_rand($users)];

    $group = $this->entityTypeManager->getStorage('group')->create([
      'type' => $group_type,
      'langcode' => $this->getLangcode($results),
      'label' => $this->getRandom()->sentences(mt_rand(1, $results['label_length']), TRUE),
      'uid' => $uid,
      'created' => REQUEST_TIME - mt_rand(0, $results['time_range']),
    ]);

    // Mark group as devel generated in case other code needs to know.
    $group->devel_generate = $results;

    // Populate all fields with sample values.
    $this->populateFields($group);

    $group->save();

  }

  /**
   * Determine language based on $results.
   *
   * @param array $results
   *   The input values from the settings form with some additional data needed
   *   for the generation.
   */
  protected function getLangcode($results) {
    if (isset($results['add_language'])) {
      $langcodes = $results['add_language'];
      $langcode = $langcodes[array_rand($langcodes)];
    }
    else {
      $langcode = $this->languageManager->getDefaultLanguage()->getId();
    }
    return $langcode;
  }

  /**
   * Retrive 50 uids from the database.
   *
   * @return array
   *   An array of the first 50 uids on the site (excluding anonymous).
   */
  protected function getUsers() {
    /** @var \Drupal\Core\Entity\EntityStorageInterface $user_storage */
    $user_storage = $this->entityTypeManager->getStorage('user');
    $query = $user_storage->getQuery();
    $query->condition('uid', 0, '<>');
    $query->range(0, 50);
    return $query->execute();
  }

}

