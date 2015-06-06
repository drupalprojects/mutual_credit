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
  public function accessOp(AccountInterface $account) {
    return empty($this->transaction->get('xid')->value) &&
      $this->transaction->payer->entity->access('payout') &&
      $this->transaction->payee->entity->access('payin');
  }

  /**
   * {@inheritdoc}
   */
  public function execute(array $context) {

    $saved = $this->transaction->save();

    \Drupal::logger('mcapi')->notice(
      'User @uid created transaction @serial',
      [
        '@uid' => \Drupal::currentUser()->id(),
        '@serial' => $this->transaction->serial->value
      ]
    );
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

  /**
   * {@inheritdoc}
  */
  public function accessState(AccountInterface $account) {
    return TRUE;
  }

  /**
   * {@inheritdoc}
  */
  static function accessSettingsElement(&$element, $default) {
    //special case, no settings
  }

}
