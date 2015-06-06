<?php

/**
 * @file
 *  Contains Drupal\mcapi\Plugin\Transitions\Erase
 *
 */

namespace Drupal\mcapi\Plugin\Transition;

use Drupal\mcapi\CurrencyInterface;
use Drupal\mcapi\Entity\State;
use Drupal\mcapi\Plugin\TransitionBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Config\ImmutableConfig;

/**
 * Undo transition
 *
 * @Transition(
 *   id = "erase"
 * )
 */
class Erase extends TransitionBase {

  /**
   * @see \Drupal\mcapi\TransitionBase::buildConfigurationForm()
   */
  static function settingsFormTweak(array &$form, FormStateInterface $form_state, ImmutableConfig $config) {
    $form['states'][TRANSACTION_STATE_ERASED] = [
      '#disabled' => TRUE,//setting #default value seems to have no effect
    ];
  }
  /**
   *  access callback for transaction transition 'erase'
   *  @return boolean
  */
  public function accessOp(AccountInterface $account) {
    if ($this->transaction->state->target_id != TRANSACTION_STATE_ERASED) {
      return parent::accessOp($this->transaction, $account);
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
  */
  public function execute(array $context) {

    $key_value_store = \Drupal::service('keyvalue.database')
      ->get('mcapi_erased')
      ->set($this->transaction->serial->value, $this->transaction->state->target_id);

    $saved = $this->transaction
      ->set('state', TRANSACTION_STATE_ERASED)//will be saved later
      ->save();

    return ['#markup' => $this->t('The transaction is erased.')];
  }

}
