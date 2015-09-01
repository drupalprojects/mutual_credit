<?php

namespace Drupal\mcapi\Plugin\Block;

/**
 * @file
 * Contains \Drupal\mcapi\Plugin\Block\BalanceHistory
 */

use Drupal\mcapi\Plugin\Block\McapiBlockBase;
use Drupal\Core\Form\FormStateInterface;


/**
 * Provides a user balances block.
 *
 * @Block(
 *   id = "mcapi_balance_history",
 *   admin_label = @Translation("Balance over time"),
 *   category = @Translation("Community Accounting")
 * )
 */
class BalanceHistory extends McapiBlockBase {

  public function defaultConfiguration() {
    $conf = parent::defaultConfiguration();
    //these are the same defaults as in hook_theme
    return $conf += array('width' => 300, 'height' => 150);
  }

  public function blockForm($form, FormStateInterface $form_state) {
    $form = parent::blockForm($form, $form_state);
    $form['width'] = array(
      '#title' => t('Width in pixels'),
      '#type' => 'number',
      '#min' => 0,
      '#default_value' => $this->configuration['width'],
      '#max_size' => 4,
      '#required' => TRUE
    );
    $form['height'] = array(
      '#title' => t('Height in pixels'),
      '#type' => 'number',
      '#min' => 0,
      '#default_value' => $this->configuration['height'],
      '#max_size' => 4,
      '#required' => TRUE
    );
    return $form;
  }
  /**
   * {@inheritdoc}
   * How do we calculate the block title? Could be singular or plural, for example
   */
  public function getTitle() {
    return $this->formatPlural(
      $this->configuration['curr_ids'],
      'Balance over time',
      'Balances over time'
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    parent::build();
    return array(
      '#theme' => 'balance_histories_gchart',
      //this isn't working
      '#attached' =>array('library' => array('http://www.google.com/jsapi')),//how to put this in the theme layer
      '#account' => $this->account,
      '#curr_ids' => $this->configuration['curr_ids'],
      '#width' => $this->configuration['width'],
      '#height' => $this->configuration['height']
    );
  }
}
