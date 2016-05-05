<?php

/**
 * @file
 * Contains \Drupal\mcapi_forms\Plugin\Block\FirstParty
 */

namespace Drupal\mcapi_forms\Plugin\Block;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\mcapi\Entity\Transaction;


/**
 * Provides a designed transaction form
 *
 * @Block(
 *   id = "mcapi_1stparty",
 *   admin_label = @Translation("Transaction form"),
 *   category = @Translation("Community Accounting")
 * )
 */
class FirstParty extends BlockBase {

  function build() {
    return \Drupal::service('entity.form_builder')
      ->getForm(
        Transaction::create(),
        $this->configuration['mode']
      );
  }

  /**
   * {@inheritdoc}
   */
  public function access(AccountInterface $account, $return_as_object = false) {
    $access = parent::access($account, TRUE);
    if ($access->allowed()) {
      $route_name = \Drupal::service('current_route_match')->getRouteName();
      //the block is available if the main page is not already a transaction form
      if (substr($route_name, 0, 14) == 'mcapi.1stparty') {
        $access = AccessResult::forbidden();
      }
      //or an operation form)
      elseif($route_name == 'mcapi.transaction.operation') {
        $access = AccessResult::forbidden();
      }
    }
    return $return_as_object ? $access : $access->isAllowed();
  }


  /**
   * {@inheritdoc}
   */
  function blockForm($form, FormStateInterface $form_state) {
    $options = [];
    foreach (mcapi_form_displays_load() as $mode => $display) {
      $options[$mode] = $display->label();
    }
    $form['mode'] = [
      '#title' => t('Display'),
      '#type' => 'select',
      '#options' => $options,
      '#default' => $this->configuration['mode']
    ];
    return $form;
  }


  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $this->configuration['mode'] = $form_state->getValues()['mode'];
  }

}
