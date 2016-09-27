<?php

namespace Drupal\mcapi_exchanges\Plugin\views\access;

use Drupal\group\Entity\GroupRole as Role;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\CacheableDependencyInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\Context\ContextProviderInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\views\Plugin\views\access\AccessPluginBase;
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
   * The exchange entity for the current user.
   */
  protected $exchangeContext;

  /**
   * The Role storage handler
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $roleStorage;

  /**
   * Constructs a Role object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Plugin\Context\ContextProviderInterface $context_provider
   *   The group route context.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, $entity_type_manager, ContextProviderInterface $context_provider) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->roleStorage = $entity_type_manager->getStorage('group_role');
    $this->exchangeContext = $context_provider->getRuntimeContexts(['exchange'])['exchange'];
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('mcapi_exchanges.exchange_context')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function access(AccountInterface $account) {
    $exchange = $this->exchangeContext->getContextValue();
    if (!empty($exchange)) {
      foreach ($this->options['role_ids'] as $rid) {
        if($exchange->hasRole($rid, $account)) {
          return TRUE;
        }
      }
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function alterRouteDefinition(Route $route) {
    if ($rids = array_filter($this->options['role_ids'])) {
      $route->setRequirement('_exchange_role', implode(';', $rids));
    }
    else {
      \Drupal::logger('mcapi_exchanges')->warning('No roles specified for route @route', ['@route' => $route->getPath()]);
    }
    // Upcast any %group path key the user may have configured so the
    // '_group_role' access check will receive a properly loaded group.
    $route->setOption('parameters', ['group' => ['type' => 'entity:group']]);
  }

  /**
   * {@inheritdoc}
   */
  public function summaryTitle() {
    foreach (Role::loadMultiple($this->options['role_ids']) as $role) {
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
      '#multiple' => TRUE
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
