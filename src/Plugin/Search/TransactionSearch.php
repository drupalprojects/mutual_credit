<?php

/**
 * @file
 * Contains \Drupal\user\Plugin\Search\TransactionSearch.
 */

namespace Drupal\mcapi\Plugin\Search;

use Drupal\mcapi\Entity\TransactionInterface;
use Drupal\mcapi\Entity\Transaction;
use Drupal\Core\Database\Connection;
use Drupal\Core\Database\Query\Condition;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessibleInterface;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\Search\SearchQuery;
use Drupal\search\Plugin\SearchIndexingInterface;
use Drupal\search\Plugin\ConfigurableSearchPluginBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Component\Utility\SafeMarkup;

/**
 * Executes a keyword search for transactions against the {mcapi_transaction}
 *
 * @SearchPlugin(
 *   id = "mcapi_search",
 *   title = @Translation("Transactions"),
 *   path = "transaction"
 * )
 */
class TransactionSearch extends ConfigurableSearchPluginBase implements AccessibleInterface, SearchIndexingInterface {

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * The entity manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The Renderer service to format the username and node.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * The list of options and info for advanced search filters.
   *
   * Each entry in the array has the option as the key and and for its value, an
   * array that determines how the value is matched in the database query. The
   * possible keys in that array are:
   * - column: (required) Name of the database column to match against.
   * - join: (optional) Information on a table to join. By default the data is
   *   matched against the {node_field_data} table.
   * - operator: (optional) OR or AND, defaults to OR.
   *
   * @var array
   */
  protected $advanced = [
    'state' => [
      'column' => 'tx.state'
    ],
    'type' => [
      'column' => 'tx.type'
    ]
  ];

  /**
   * {@inheritdoc}
   */
  static public function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $container->get('database'),
      $container->get('entity_type.manager'),
      $container->get('renderer'),
      $configuration,
      $plugin_id,
      $plugin_definition
    );
  }

  /**
   * Creates a UserSearch object.
   *
   * @param Connection $database
   *   The database connection.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity manager.
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param array $plugin_definition
   *   The plugin implementation definition.
   */
  public function __construct(Connection $database, EntityTypeManagerInterface $entity_type_manager, RendererInterface $renderer, array $configuration, $plugin_id, array $plugin_definition) {
    $this->database = $database;
    $this->entityTypeManager = $entity_type_manager;
    $this->renderer = $renderer;
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   * //@todo determine how search access works
   */
  public function access($operation, AccountInterface $account = NULL, $return_as_object = false) {
    $result = $account->isAuthenticated() ?
      AccessResult::allowed() :
      AccessResult::forbidden();
    return $return_as_object ? $result : $result->isAllowed();

  }

  /**
   * {@inheritdoc}
   * this is borrowed from nodeSearch and is overcomplicated
   */
  public function execute() {
    if ($this->isSearchExecutable()) {
      $results = $this->findResults();
      if ($results) {
        return $this->prepareResults($results);
      }
    }
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function searchFormAlter(array &$form, FormStateInterface $form_state) {
    // Add advanced search keyword-related boxes.
    $form['advanced'] = [
      '#type' => 'details',
      '#title' => t('Advanced search'),
      '#attributes' => ['class' => ['search-advanced']],
    ];
    $form['advanced']['types-fieldset']['type'] = [
      '#type' => 'checkboxes',
      '#title' => t('Only of the type(s)'),
      '#prefix' => '<div class="criterion">',
      '#suffix' => '</div>',
      '#options' => mcapi_entity_label_list('mcapi_type'),
    ];
    $form['advanced']['types-fieldset']['state'] = [
      '#type' => 'checkboxes',
      '#title' => t('Only in the state(s)'),
      '#prefix' => '<div class="criterion">',
      '#suffix' => '</div>',
      '#options' => mcapi_entity_label_list('mcapi_state'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function isSearchExecutable() {
    return !empty($this->keywords) || (isset($this->searchParameters['f']) && count($this->searchParameters['f']));
  }

  /**
   * Queries to find search results, and sets status messages.
   *
   * This method can assume that $this->isSearchExecutable() has already been
   * checked and returned TRUE.
   *
   * @return \Drupal\Core\Database\StatementInterface|null
   *   Results from search query execute() method, or NULL if the search
   *   failed.
   * @note borrowed from nodeSearch - too complicated
   */
  protected function findResults() {
    // Build matching conditions.
    $query = $this->database
      ->select('search_index', 'i', array('target' => 'replica'))
      ->extend('Drupal\Core\Database\Query\PagerSelectExtender')
      ->extend('Drupal\search\SearchQuery');//this overrides the query::execute() method


    $query->join('mcapi_transaction', 'tx', 'tx.xid = i.sid');
    $query->condition('tx.state', 'erased', '<>')
      ->addTag('mcapi_transaction')
      ->searchExpression($this->keywords, $this->getPluginId());
    //taken from node_search beta11
    // Handle advanced search filters in the f query string.
    // \Drupal::request()->query->get('f') is an array that looks like this in
    // the URL: ?f[]=type:page&f[]=term:27&f[]=term:13&f[]=langcode:en
    // So $parameters['f'] looks like:
    // array('type:page', 'term:27', 'term:13', 'langcode:en');
    // We need to parse this out into query conditions, some of which go into
    // the keywords string, and some of which are separate conditions.
    $parameters = $this->getParameters();
    if (!empty($parameters['f']) && is_array($parameters['f']) && 0) {
      $filters = array();
      // Match any query value that is an expected option and a value
      // separated by ':' like 'term:27'.
      $pattern = '/^(' . implode('|', array_keys($this->advanced)) . '):([^ ]*)/i';
      foreach ($parameters['f'] as $item) {
        if (preg_match($pattern, $item, $m)) {
          // Use the matched value as the array key to eliminate duplicates.
          $filters[$m[1]][$m[2]] = $m[2];
        }
      }

      // Now turn these into query conditions. This assumes that everything in
      // $filters is a known type of advanced search.
      foreach ($filters as $option => $matched) {
        $info = $this->advanced[$option];
        // Insert additional conditions. By default, all use the OR operator.
        $operator = empty($info['operator']) ? 'OR' : $info['operator'];
        $where = new Condition($operator);
        foreach ($matched as $value) {
          $where->condition($info['column'], $value);
        }
        $query->condition($where);
        if (!empty($info['join'])) {
          $query->join($info['join']['table'], $info['join']['alias'], $info['join']['condition']);
        }
      }
    }
    //this is where we could tweak the query to reorder the search according to search settings

    $find = $query
      ->limit(10)
      ->execute();

    if ($query->getStatus() & SearchQuery::LOWER_CASE_OR) {
      drupal_set_message($this->t('Search for either of the two terms with uppercase <strong>OR</strong>. For example, <strong>cats OR dogs</strong>.'), 'warning');
    }

    return $find;
  }


  /**
   * Prepares search results for rendering.
   *
   * @param \Drupal\Core\Database\StatementInterface $found
   *   Results found from a successful search query execute() method.
   *
   * @return array
   *   Array of search result item render arrays (empty array if no results).
   * @note borrowed from nodeSearch - too complicated
   */
  protected function prepareResults(StatementInterface $found) {
    $results = array();
    $keys = $this->keywords;

    foreach ($found as $item) {
      // Render the transaction.
      $tx = Transaction::load($item->sid);
      $build = ['#markup' => SafeMarkup::escape($tx->description->value)]
        + $this->entityTypeManager
        ->getViewBuilder('mcapi_transaction')
        ->view($tx, 'search_result');

      unset($build['#theme']);

      $rendered = SafeMarkup::set($this->renderer->render($build));
      //see template_preprocess_search_result
      //search result theming is not v good and not well documented in beta 11
      $result = array(
        'link' => $tx->url(
          'canonical',
          ['absolute' => TRUE]
        ),
        'title' => $tx->description->value,
        'date' => $tx->created->value,//not used
        'score' => $item->calculated_score,//not used
      );
      $results[] = $result;
    }
    return $results;
  }

  /**
   * {@inheritdoc}
   */
  public function indexClear() {
    search_index_clear($this->getPluginId());
  }

  /**
   * {@inheritdoc}
   */
  public function markForReindex() {
    search_mark_for_reindex($this->getPluginId());
  }
  /**
   * {@inheritdoc}
   */
  public function indexStatus() {
    $q = "SELECT COUNT(DISTINCT tx.xid) "
      . "FROM {mcapi_transaction} tx "
      . "LEFT JOIN {search_dataset} sd ON sd.sid = tx.xid AND sd.type = :type "
      . "WHERE sd.sid IS NULL OR sd.reindex <> 0";
    return [
      'remaining' => $this->database->query($q, [':type' => $this->getPluginId()])->fetchField(),
      'total' => $this->database->query('SELECT COUNT(*) FROM {mcapi_transaction}')->fetchField()
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function updateIndex() {
    $xids = $this->database->queryRange(
      "SELECT tx.xid, MAX(sd.reindex) "
      . "FROM {mcapi_transaction} tx "
      . "LEFT JOIN {search_dataset} sd "
      . "ON sd.sid = tx.xid AND sd.type = :type "
      . "WHERE sd.sid IS NULL OR sd.reindex <> 0 "
      . "GROUP BY tx.xid "
      . "ORDER BY MAX(sd.reindex) is null DESC, "
      . "MAX(sd.reindex) ASC, tx.xid ASC",
      0,
      50, //this is hard-coded for now
      [':type' => $this->getPluginId()],
      ['target' => 'replica']
    )->fetchCol();
    if (!$xids) {
      return;
    }

    $storage = $this->entityTypeManager->getStorage('mcapi_transaction');
    foreach ($storage->loadMultiple($xids) as $tx) {
      //index only parent transactions
      if ($tx->parent->value) {
        continue;
      }
      $this->indexTransaction($tx);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    $configuration = [
      'order' => 'relevance'
    ];
    return $configuration;
  }

  /**
   * Indexes a single ad.
   *
   * @param TransactionInterface $transaction
   *   The transaction to index.
   */
  protected function indexTransaction(TransactionInterface $transaction) {
    search_index(
      $this->getPluginId(),
      $transaction->id(),
      'und',
      SafeMarkup::checkPlain($transaction->description->value)
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form['order'] = [
      '#title' => $this->t('Result order', [], ['context' => 'search']),
      //'#description' => 'not working yet, and even then, only with the geo modules',
      '#type' => 'radios',
      '#options' => [
        'relevance' => $this->t('Keyword relevance'),
        'created' => $this->t('Created date'),
        'value' => $this->t('Raw value'),
      ],
      '#default_value' => $this->configuration['order']
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $this->configuration['order'] = $form_state->getValue('order');
  }


  /*
   * {@inheritdoc}
   */
  public function buildSearchUrlQuery(FormStateInterface $form_state) {
    // Grab the keywords entered in the form and put them as 'keys' in the GET.
    $keys = trim($form_state->getValue('keys'));
    $query = ['keys' => $keys];
    foreach (['type', 'state'] as $prop) {
      if ($form_state->hasValue($prop) && is_array($form_state->getValue($prop))) {
        foreach ($form_state->getValue($prop) as $type) {
          if ($type) {
            $filters[] = "$prop:" . $type;
          }
        }
      }
    }

    if ($filters) {
      $query['f'] = $filters;
    }

    return $query;
  }

}
