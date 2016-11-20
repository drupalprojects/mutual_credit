<?php

namespace Drupal\mcapi_exchanges\Plugin\views\access;

use Drupal\group\Entity\GroupRole;
use Drupal\views\Plugin\views\access\AccessPluginBase;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\CacheableDependencyInterface;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Routing\Route;

/**
 * Access plugin that provides group role-based access control. Unlike permission
 * based access, this isn't automatically granted to user 1.
 *
 * @ingroup views_access_plugins
 *
 * @ViewsAccess(
 *   id = "exchange_role",
 *   title = @Translation("Role in Exchange"),
 *   help = @Translation("Access will be granted to users with the specified role in their exchange.")
 * )
 */
class ExchangeRole extends AccessPluginBase implements CacheableDependencyInterface {

  /**
   * {@inheritdoc}
   */
  protected $usesOptions = TRUE;

  /**
   * @var Drupal\group\GroupInterface.
   */
  protected $exchange;

  /**
   * The Role storage handler
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $roleStorage;

  /**
   * Constructor
   *
   * @param EntityTypeManager $entity_type_manager
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManager $entity_type_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->roleStorage = $entity_type_manager->getStorage('group_role');
    if ($group_content = group_exclusive_membership_get('exchange')) {
      $this->exchange = $group_content->getGroup();
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function access(AccountInterface $account) {
    if ($this->exchange) {
      foreach ($this->options['role_ids'] as $rid) {
        if($this->exchange->hasRole($rid, $account)) {
          return TRUE;
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function alterRouteDefinition(Route $route) {
    $route->setRequirement('_exchange_role', implode(';', array_filter($this->options['role_ids'])));
    // Upcast any %group path key the user may have configured so the
    // '_group_role' access check will receive a properly loaded group.
    $route->setOption('parameters', ['group' => ['type' => 'entity:group']]);
  }

  /**
   * {@inheritdoc}
   */
  public function summaryTitle() {
    foreach (GroupRole::loadMultiple($this->options['role_ids']) as $role) {
      $title[] = $role->label();
    }
    return implode(', ', $title);
  }

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();
    // @todo find a better default
    $options['role_ids'] = array('default' => NULL);
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);
    // Get list of exchange roles
    $roles = [];

    $roles = $this->roleStorage->loadByProperties(['group_type' => 'exchange']);
    foreach ($roles as $role_id => $role) {
      $options[$role_id] = strip_tags($role->label());
    }

    $form['role_ids'] = array(
      '#type' => 'checkboxes',
      '#description' => $this->t('N.B. This is a way of filtering by group type.'),
      '#options' => $options,
      '#title' => $this->t('Group roles'),
      '#default_value' => $this->options['role_ids'],
      '#description' => $this->t('Only users with a selected exchange role will be able to access this display.<br /><strong>Warning:</strong> This will only work if there is an exchange {group} parameter in the route. If not, it will always deny access.'),
      '#multiple' => TRUE,
      '#required' => TRUE
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheMaxAge() {
    return Cache::PERMANENT;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts() {
    return ['group_membership.roles.permissions'];
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheTags() {
    return ['route', 'user'];
  }

}
