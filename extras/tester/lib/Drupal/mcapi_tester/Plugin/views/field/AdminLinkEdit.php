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

/**
 * Field handler to present a link to user edit.
 *
 * @ingroup views_field_handlers
 *
 * @PluginID("admin_link_edit")
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
        $perms[$module_info[$module]['name']][$name] = strip_tags($perm['title']);
      }
    }  
    $form['permission'] = array(
      '#type' => 'select',
      '#title' => t('Permission'),
      '#description' => t('Users with which permission can see blocked users?'),
      '#options' => $perms,
      '#default_value' => $this->value['permission']
    );
    parent::buildOptionsForm($form, $form_state);
  }
  protected function defineOptions() {
    $options = parent::defineOptions();

    $options['permission'] = array('default' => '');

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

  /**
   * {@inheritdoc}
   */
  protected function renderLink(EntityInterface $entity, ResultRow $values) {
    if ($entity && $entity->access('update')) {
      $this->options['alter']['make_link'] = TRUE;

      $text = !empty($this->options['text']) ? $this->options['text'] : t('Edit');

      $uri = $entity->uri();
      $this->options['alter']['path'] = $uri['path'] . '/edit';
      $this->options['alter']['query'] = drupal_get_destination();

      return $text;
    }
  }

}
