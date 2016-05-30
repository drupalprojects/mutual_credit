<?php

/**
 * @file
 * Definition of Drupal\mcapi\Plugin\views\field\WalletHolder.
 */

namespace Drupal\mcapi\Plugin\views\field;

use Drupal\views\Plugin\views\field\Standard;
use Drupal\views\ResultRow;
use Drupal\Core\Form\FormStateInterface;

/**
 * Field handler to link the transaction description to the transaction itself
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("mcapi_wallet_holder")
 */
class WalletHolder extends Standard {

  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['owner'] = array('default' => '');
    return $options;
  }

  /**
   * Default options form that provides the label widget that all fields
   * should have.
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);
    $form['owner']  = [
      '#title' => $this->t('Show the wallet owner'),
      '#description' => $this->t('The holder can be any entity but the owner is always a user'),
      '#type' => 'checkbox',
      '#default_value' => $this->options['owner']
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
    //$this->addAdditionalFields();
  }

  /**
   * {@inheritdoc}
   */
  function render(ResultRow $values) {
    $wallet = $this->getEntity($values);
    
    $entity = $this->options['owner'] ?
      $wallet->getHolder()->getOwner() :
      $wallet->getHolder();
    return $entity->toLink()->toString();
  }

}
