<?php

namespace Drupal\mcapi_exchanges\Plugin\views\access;

use Drupal\group\Plugin\views\access\GroupRole;
use Drupal\group\Entity\GroupRole as GroupRoleEntity;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\CacheableDependencyInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\Context\ContextProviderInterface;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Routing\Route;

/**
 * Access plugin that provides group role-based access control.
 *
 * @ingroup views_access_plugins
 *
 * @ViewsAccess(
 *   id = "exchange_role",
 *   title = @Translation("Exchange role"),
 *   title = @Translation("Current user's role in their exchange"),
 * )
 */
class ExchangeRole extends GroupRole implements CacheableDependencyInterface {

  /**
   * {@inheritdoc}
   */
  protected $usesOptions = TRUE;

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The group entity from the route.
   *
   * @var \Drupal\group\Entity\GroupInterface
   */
  protected $group;

  /**
   * Constructs a Role object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   * @param \Drupal\Core\Plugin\Context\ContextProviderInterface $context_provider
   *   The group route context.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, ModuleHandlerInterface $module_handler, ContextProviderInterface $context_provider) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $module_handler, $context_provider);
    $this->moduleHandler = $module_handler;

    /** @var \Drupal\Core\Plugin\Context\ContextInterface[] $contexts */
    $contexts = $context_provider->getRuntimeContexts();
    $this->group = $contexts['group']->getContextValue();
  }

    /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('module_handler'),
      $container->get('group.exchange_context')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function access(AccountInterface $account) {
    if (!empty($this->group)) {
      return $this->group->hasRole($this->options['group_role'], $account);
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function alterRouteDefinition(Route $route) {
    $route->setRequirement('_group_role', $this->options['group_role']);

    // Upcast any %group path key the user may have configured so the
    // '_group_role' access check will receive a properly loaded group.
    $route->setOption('parameters', ['group' => ['type' => 'entity:group']]);
  }

  /**
   * {@inheritdoc}
   */
  public function summaryTitle() {
    return $this->t(
      'Group role: %name',
      ['%name' => GroupRoleEntity::load($this->options['group_role'])->label()]
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['group_role'] = array('default' => 'view group');
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);

    // Get list of roles
    foreach (GroupRoleEntity::loadMultiple() as $role_id => $role) {
      $roles[$role->getGroupType()->label()][$role_id] = $role->label();
    }

    $description = (string)$this->t('Only users with the selected group role will be able to access this display.');
    $description .= ' '.(string)$this->t('<strong>Warning:</strong> This will only work if there is a {group} parameter in the route. If not, it will always deny access.');
    $description = ' '.(string)$this->t('The role must be congruent with the type of group being displayed.');

    $form['group_role'] = array(
      '#type' => 'select',
      '#options' => $roles,
      '#title' => $this->t('Group role'),
      '#default_value' => $this->options['group_role'],
      '#description' => $description
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
    //@todo we haven't make a cacheContext service for roles yet
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheTags() {
    return [];
  }

}
