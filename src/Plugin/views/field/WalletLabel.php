<?php

/**
 * @file
 * Definition of Drupal\mcapi\Plugin\views\field\WalletLabel.
 */

namespace Drupal\mcapi\Plugin\views\field;

use Drupal\views\Plugin\views\field\Standard;
use Drupal\views\ResultRow;
use Drupal\Core\Form\FormStateInterface;

/**
 * Virtual field handler to show the wallet's name
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("mcapi_wallet_label")
 */
class WalletLabel extends Standard {

    /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['link'] = array('default' => TRUE);
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
     $form['link'] = array(
      '#title' => $this->t("Link to wallet"),
      '#type' => 'checkbox',
      '#default_value' => !empty($this->options['link']),
    );
    parent::buildOptionsForm($form, $form_state);
  }

  public function query() {
    //$this->ensureMyTable();
  }

  /**
   * {@inheritdoc}
   */
  function render(ResultRow $values) {
    $wallet = $this->getEntity($values);
    return $this->options['link']
      ? $wallet->link()
      : $wallet->label();
  }

}
