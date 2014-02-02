<?php
/**
 * @file
 * Definition of Drupal\views\Plugin\views\filter\UserActive.
 */

namespace Drupal\mcapi_tester\Plugin\views\filter;

use Drupal\views\Plugin\views\filter\FilterPluginBase;

/**
 * Shows only active users unless the current user has a given permission
 *
 * @ingroup views_filter_handlers
 *
 * @PluginID("user_active")
 */
class UserActive extends FilterPluginBase {

  protected function valueForm(&$form, &$form_state) {
    // Get list of permissions
    foreach (\Drupal::moduleHandler()->getImplementations('permission') as $module) {
      $permissions = module_invoke($module, 'permission');
      foreach ($permissions as $name => $perm) {
        $perms[$module_info[$module]['name']][$name] = strip_tags($perm['title']);
      }
    }  
    $form['permission'] = array(
      '#type' => 'select',
      '#title' => t('Permission'),
      '#description' => t('Users with which permission can see blocked users?')
      '#options' => $perms,
      '#default_value' => $this->value['permission']),
    );
    parent::valueForm($form, $form_state);
  }


  protected function defineOptions() {
    $options['permission']['default'] = 'administer users';
    return $options;
  }


  /**
   * {@inheritdoc}
   */
  public function query() {
    $this->ensureMyTable();
    $field = "$this->tableAlias.$this->realField";

    $permission = $this->options['permission'];
    if (!\Drupal::currentUser()->hasPermission($this->options['permission'])) {
      $this->query->addWhere($this->options['group'], $field, 1, '=');
    }
  }
}
