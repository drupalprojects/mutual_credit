<?php

/**
 * @file
 * Definition of Drupal\mcapi\Plugin\views\field\User.
 */

namespace Drupal\mcapi\Plugin\views\field;

use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\Plugin\views\display\DisplayPluginBase;
use Drupal\Component\Annotation\PluginID;
use Drupal\views\ResultRow;
use Drupal\views\ViewExecutable;

/**
 * Field handler to provide simple renderer that allows linking to a user.
 *
 * @ingroup views_field_handlers
 *
 * @PluginID("trader")
 */
class Trader extends FieldPluginBase {


  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['link_to_user'] = array('default' => TRUE, 'bool' => TRUE);
    return $options;
  }

  /**
   * Provide link to node option
   */
  public function buildOptionsForm(&$form, &$form_state) {
    $form['link_to_user'] = array(
      '#title' => t('Link this field to its user'),
      '#description' => t("Enable to override this field's links."),
      '#type' => 'checkbox',
      '#default_value' => $this->options['link_to_user'],
    );
    parent::buildOptionsForm($form, $form_state);
  }


  /**
   * {@inheritdoc}
   */
  public function render(ResultRow $values) {
    $uid = $this->getValue($values);
    if (!empty($this->options['link_to_user']) && user_access('access user profiles') && $uid !== NULL && $uid !== '') {
      $this->options['alter']['make_link'] = TRUE;
      $this->options['alter']['path'] = 'user/'. $uid;
  }
    return $uid;
  }

}
