<?php

/**
 * @file
 * Contains Drupal\mcapi\Plugin\Transitions\View
 * View is special because it's not actually a transition.
 * So this class removes several pieces from the transaction base
 */

namespace Drupal\mcapi\Plugin\Transition;

use Drupal\mcapi\CurrencyInterface;
use Drupal\mcapi\Plugin\TransitionBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Config\ImmutableConfig;

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
      $this->configuration['redirect'],
      $this->configuration['message']
    );
  }

  /*
   * {@inheritdoc}
   */
  public function accessOp(AccountInterface $account) {
    //you can view a transaction if you can view either the payer OR payee wallets
    return $this->transaction->payer->entity->access('details', $account)
    || $this->transaction->payee->entity->access('details', $account);
  }

  /*
   * {@inheritdoc}
  */
  static function settingsFormTweak(array &$form, FormStateInterface $form_state, ImmutableConfig $config) {
    $form['sure']['#title'] = t('Display page');
    //because the view transition isn't a transaction and doesn't have any buttons
    unset(
      $form['sure']['button'],
      $form['sure']['cancel_button'],
      $form['sure']['display'],
      $form['feedback'],
      $form['access']
    );
  }

  public function execute(array $context){
    //no execution for this special transition
  }

}
