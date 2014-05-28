<?php

/**
 * @file
 * Definition of Drupal\mcapi_tester\Plugin\views\field\AdminLinkEdit.
 */

namespace Drupal\mcapi_tester\Plugin\views\field;

use Drupal\user\Plugin\views\field\LinkEdit;
use Drupal\Core\Entity\EntityInterface;
use Drupal\views\ResultRow;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Field handler to present a link to user edit.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("admin_link_edit")
 */
class AdminLinkEdit extends LinkEdit {


  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, &$form_state) {
    parent::buildOptionsForm($form, $form_state);
    // Get list of permissions
    foreach (\Drupal::moduleHandler()->getImplementations('permission') as $module) {
      $permissions = module_invoke($module, 'permission');
      foreach ($permissions as $name => $perm) {
        $perms[$module][$name] = strip_tags($perm['title']);
      }
    }  
    $form['permission'] = array(
      '#type' => 'select',
      '#title' => t('Permission'),
      '#description' => t('Users with which permission can see blocked users?'),
      '#options' => $perms,
      '#default_value' => $this->options['permission']
    );
    unset($form['exclude']);
  }
  protected function defineOptions() {
    $options = parent::defineOptions();

    $options['permission'] = array('default' => 'manage own exchanges');

    return $options;
  }

  public function access(AccountInterface $account) {
    return user_access($this->options['permission'], $account);
  }

}
