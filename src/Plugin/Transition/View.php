<?php

/**
 * @file
 * Contains Drupal\mcapi\Plugin\Transitions\View
 * View is special because it's not actually a transition.
 * So this class removes several pieces from the transaction base
 */

namespace Drupal\mcapi\Plugin\Transition;

use Drupal\mcapi\TransactionInterface;
use Drupal\mcapi\CurrencyInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\mcapi\Plugin\TransitionBase;

/**
 * Links to the transaction certificate
 *
 * @Transition(
 *   id = "view"
 * )
 */
class View extends TransitionBase {//does it go without saying that this implements TransitionInterface


  function __construct(array $configuration, $plugin_id, array $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    unset(
      $this->configuration['button'],
      $this->configuration['cancel_button'],
      $this->configuration['access'],
      $this->configuration['format2'],
      $this->configuration['redirect'],
      $this->configuration['twig2'],
      $this->configuration['message']
    );
  }

  /*
   * {@inheritdoc}
   */
  public function accessOp(TransactionInterface $transaction, AccountInterface $account) {
    //you can view a transaction if you can view either the payer OR payee wallets
    return $transaction->payer->entity->access('details', $account)
    || $transaction->payee->entity->access('details', $account);
  }


  /*
   * {@inheritdoc}
  */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);
    $form['sure']['#title'] = t('Display page');
    //because the view transition isn't a transaction and doesn't have any buttons
    unset($form['sure']['button'], $form['sure']['cancel_button']);
    //because there is no second step
    unset($form['feedback']);
    //because transaction view access is handled according to wallet access functions
    unset($form['access']);
    return $form;
  }

  public function execute(TransactionInterface $transaction, array $context){}

}
