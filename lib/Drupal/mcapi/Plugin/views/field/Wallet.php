<?php

/**
 * @file
 * Definition of Drupal\mcapi\Plugin\views\field\Wallet.
 */

namespace Drupal\mcapi\Plugin\views\field;

use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\Plugin\views\field\Standard;
use Drupal\views\Plugin\views\display\DisplayPluginBase;
use Drupal\Component\Annotation\PluginID;
use Drupal\views\ResultRow;

/**
 * Field handler to link the transaction description to the transaction itself
 *
 * @ingroup views_field_handlers
 *
 * @PluginID("wallet")
 */
class Wallet extends Standard {

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['parent'] = array('default' => TRUE);
    $options['link'] = array('default' => TRUE);
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, &$form_state) {
    //this might mean we don't need a relationship
    $form['parent'] = array(
      '#title' => t('Show name of the parent entity'),
      '#type' => 'checkbox',
      '#default_value' => $this->options['parent'],
    );
    $form['link'] = array(
      '#title' => t('Link to the parent entity'),
      '#type' => 'checkbox',
      '#default_value' => $this->options['link'],
    );
    parent::buildOptionsForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  function render(ResultRow $values) {
    //$fieldname = substr($this->getField(), strpos($this->getField(), '.')+1);
    $wallet = entity_load('mcapi_wallet', $this->getValue($values));
    $text = $wallet->label();
    if ($this->options['link']) {
      return l($text, $this->options['parent'] ? $wallet->getOwner()->uri() : $wallet->uri);
    }
  }
}
