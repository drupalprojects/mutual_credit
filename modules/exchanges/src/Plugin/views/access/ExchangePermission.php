<?php

namespace Drupal\mcapi_exchanges\Plugin\views\access;

use Drupal\group\Plugin\views\access\GroupPermission;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\CacheableDependencyInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Routing\Route;

/**
 * Access plugin that provides group permission-based access control.
 *
 * @ingroup views_access_plugins
 *
 * @ViewsAccess(
 *   id = "exchange_permission",
 *   title = @Translation("Current user's permission in their exchange"),
 *   help = @Translation("Grant access to users with the specified group permission in the exchange.")
 * )
 */
class ExchangePermission extends GroupPermission implements CacheableDependencyInterface {

  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('group.permissions'),
      $container->get('module_handler'),
      $container->get('group.exchange_context')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function access(AccountInterface $account) {
    if (!empty($this->group)) {
      return $this->group->hasPermission($this->options['group_permission'], $account);
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function alterRouteDefinition(Route $route) {
    $route->setRequirement('_exchange_permission', $this->options['group_permission']);

    // Upcast any %group path key the user may have configured so the
    // '_group_permission' access check will receive a properly loaded group.
    $route->setOption('parameters', ['group' => ['type' => 'entity:group']]);
  }

  /**
   * {@inheritdoc}
   */
  public function summaryTitle() {
    $permissions = $this->permissionHandler->getPermissions(TRUE);
    if (isset($permissions[$this->options['group_permission']])) {
      return $permissions[$this->options['group_permission']]['title'];
    }

    return $this->t($this->options['group_permission']);
  }

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['group_permission'] = array('default' => 'view group');
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);

    // Get list of permissions
    $permissions = [];
    foreach ($this->permissionHandler->getPermissions(TRUE) as $permission_name => $permission) {
      $display_name = $this->moduleHandler->getName($permission['provider']);
      $permissions[$display_name][$permission_name] = strip_tags($permission['title']);
    }
        $description = (string)$this->t('Only users with the selected group permission will be able to access this display.');
    $description .= (string)$this->t('<strong>Warning:</strong> This will only work if there is a {group} parameter in the route. If not, it will always deny access.');

    $form['group_permission'] = array(
      '#type' => 'select',
      '#options' => $permissions,
      '#title' => $this->t('Exchange permission'),
      '#default_value' => $this->options['group_permission'],
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
    return ['group_membership.roles.permissions'];
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheTags() {
    return [];
  }

}
