<?php

/**
 * @file
 *  Contains Drupal\mcapi\Plugin\Transition\Create
 */

namespace Drupal\mcapi\Plugin\Transition;

use Drupal\mcapi\Plugin\TransitionBase;
use Drupal\mcapi\CurrencyInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Config\ImmutableConfig;

/**
 * Create transition
 *
 * @Transition(
 *   id = "create"
 * )
 */
class Create extends TransitionBase {

  /**
   * {@inheritdoc}
   * A transaction can be created if the user has a wallet, and permission to transaction
   */
  public function accessOp(AccountInterface $acount) {
    //@todo check that the user is allowed to pay in to payee wallet AND out from payer_wallet.
    return empty($this->transaction->get('xid')->value);
  }

  /**
   * {@inheritdoc}
   */
  public function execute(array $context) {
    $this->baseExecute($context);
    //the save operation takes place elsewhere
    return ['#markup' => t('Transaction created')];
  }

  /**
   * {@inheritdoc}
  */
  static function settingsFormTweak(array &$form, FormStateInterface $form_state, ImmutableConfig $config) {
    $form['title']['#type'] = 'hidden';//because this transition never appears as a link.
    $form['tooltip']['#type'] = 'hidden';//because this transition never appears as a link.
    unset($form['states']);
  }


  public function accessState(AccountInterface $account) {
    //can we payin to the payee wallet and payout of the payer wallet
    return $this->transaction->payer->entity->access('payout')
    || $this->transaction->payee->entity->access('payin');
  }

  static function accessSettingsForm(&$element) {
    //special case, no settings
  }

}
