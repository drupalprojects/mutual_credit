<?php

/**
 * @file
 *  Contains Drupal\mcapi\Plugin\Transitions\Delete
 *
 */

namespace Drupal\mcapi\Plugin\Transition;

use Drupal\mcapi\CurrencyInterface;
use Drupal\mcapi\Entity\State;
use Drupal\mcapi\Plugin\TransitionBase;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\Form\FormStateInterface;

/**
 * Delete transition
 *
 * @Transition(
 *   id = "delete"
 * )
 */
class Delete extends TransitionBase {

  /**
   * @see \Drupal\mcapi\TransitionBase::buildConfigurationForm()
   */
  static function settingsFormTweak(array &$form, FormStateInterface $form_state, ImmutableConfig $config) {
    unset($form['access'][TRANSACTION_STATE_ERASED]);
    //if the transaction no longer exists there's nothing to configure for the final step
    unset($form['feedback']['redirect']['#states']);
    //because after a transaction is deleted, you can't very well go and visit it.
    $form['feedback']['redirect']['#required'] = TRUE;
    return $form;
  }

  /**
   * {@inheritdoc}
  */
  public function execute(array $context) {
    $this->transaction->delete();
    return array('#markup' => $this->t('The transaction is deleted.'));
  }

}
