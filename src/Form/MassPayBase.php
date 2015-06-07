<?php

/**
 * @file
 * Definition of Drupal\mcapi\Form\MassPayBase.
 * Create an cluster of transactions, based on a single entity form
 * N.B. This could be an entity form for Transaction OR Exchange.
 * If the latter, it will be appropriately populated.
 *
 */

namespace Drupal\mcapi\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\user\Entity\User;
use Drupal\mcapi\Entity\Transaction;
use Drupal\mcapi\Entity\Wallet;
use Drupal\Core\Form\FormStateInterface;

class MassPayBase extends EntityForm {


  /**
   * Overrides Drupal\mcapi\Form\TransactionForm::form().
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = ['submit' => [
      '#type' => 'submit',
      '#weight' => 50
    ]];
    if (empty($form_state->get('validated_transactions'))) {
      //we have to mimic some part of the normal entity form preparation.
      //@todo on the rebuilt form we need to make a default entity.
      //But how to get the submitted values from $form_state?
      //$this->entity = Transaction::create([]);
      //$form = parent::form($form, $form_state);
      $form['submit']['#value'] = $this->t('Preview');
      $this->step_1($form, $form_state);

    }
    else {
      $viewBuilder = \Drupal::entityManager()->getViewBuilder('mcapi_transaction');
      foreach ($form_state->get('validated_transactions') as $transaction) {
        $form['preview'][] = $viewBuilder->view($transaction, 'sentence');
      }
      $form['submit']['#value'] = $this->t('Confirm');
    }
    return $form;
  }

  public function step_1(array &$form, FormStateInterface $form_state) {
    if (empty($form_state->get('confirmed'))) {
      $types = \Drupal::config('mcapi.wallets')->get('entity_types');
      if ($types['user:user'] == 1) {
        $mode_options = [
          $this->t('The named users'),
      	  $this->t("All users except those named"),
        ];
      }
      else {
        $mode_options = [
          $this->t('The named wallets'),
          $this->t("All wallets except those named"),
        ];
      }
      $form['mode'] = [
        '#type' => 'radios',
        //@todo start with nothing selected to force the user to choose something.
        '#options' => $mode_options,
        '#weight' => '2'
      ];
      $form['payer'] = [
        '#title' => $this->t('Payer'),
        '#type' => 'select_wallet',
        '#autocomplete_route_parameters' => ['role' => 'payer']
      ];
      $form['payee'] = [
        '#title' => $this->t('Payee'),
        '#type' => 'select_wallet',
        '#autocomplete_route_parameters' => ['role' => 'payee']
      ];
      $form['description'] = [
        '#title' => $this->t('Description'),
        '#placeholder' => $this->t('What this payment is for...'),
        '#type' => 'textfield',
        '#weight' => 5
      ];
      $form['worth'] = [
        '#title' => $this->t('Worth'),
        '#type' => 'worth',
        '#weight' => 6
      ];
      $form['notification'] = [
        '#title' => $this->t('Notify everybody'),
        //@todo decide whether to put rules in a different module
        '#description' => \Drupal::moduleHandler()->moduleExists('rules') ?
          $this->t('Ensure this mail does not clash with mails sent by the rules module.') : '',
      	'#type' => 'fieldset',
        '#open' => TRUE,
        '#weight' => 20,
        'body' => [
      	  '#title' => $this->t('Message'),
          //@todo the tokens?
          //'#description' => $this->t('The following tokens are available:') .' [user:name]',
          '#type' => 'textarea',
          //this needs to be stored per-exchange. What's the best way?
          '#default_value' => \Drupal::service('user.data')->get('mcapi', $this->currentUser()->id(), 'masspay')
        ]
      ];
    }
  }

  public function validateForm(array &$form, FormStateInterface $form_state) {
    if ($form_state->getErrors()) return;
    //only validate step 1
    if (empty($form_state->get('validated_transactions'))) {
      $form_state->cleanValues();;//without this, buildentity fails, but again, not so in nodeFormController

      $form_state->setValue('creator', $this->currentUser()->id());
      $form_state->setValue('type', 'mass');
      $values = $form_state->getValues();
      $transactions = [];
      $uuid_service = \Drupal::service('uuid');
      foreach ((array)$form_state->getValue('payer') as $payer) {
        foreach ((array)$form_state->getValue('payee') as $payee) {
          if ($payer == $payee) {
            continue;
          }
          $values['payer'] = $payer;
          $values['payee'] = $payee;
          $transactions[] = Transaction::create($values);
        }
      }

      foreach ($transactions as $transaction) {
        //we do NOT add children
        $violations = $transaction->validate();
        foreach ($violations as $violation) {
          //echo ($violation->getpropertypath());
          $form_state->setErrorByName($violation->getpropertypath(), $violation->getMessage());
        }
      }
      //@todo update to d8
      //drupal_set_title(t('Are you sure?'));
      //@todo Should these / do these go in the temp store?
      $form_state->set('validated_transactions', $transactions);
      $form_state->set('wallets', array_unique(array_merge($payers, $payees)));
    }
  }


  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    //@todo how do we inject stuff into forms?
    \Drupal::service('user.data')
      ->set('mcapi', $this->currentUser()->id(), 'masspay', $values['notification']['body']);

    if (!array_key_exists('op', $values)) {
      $form_state->setRebuild(TRUE);
    }
    else {
      $main_transaction = array_shift($form_state->get('validated_transactions'));
      $main_transaction->children = $form_state->get('validated_transactions');
      $main_transaction->save();

      //mail the owners of all the wallets involved.
      foreach (Wallet::loadMultiple($form_state->get('wallets')) as $wallet) {
        $uids[] = $wallet->getOwnerId();
      }
      foreach (User::loadMultiple(array_unique($uids)) as $account) {
        $to[] = $account->getEmail();
      }
      //@todo make sure this is queueing
      //the mail body has been saved against the currentUser
      \Drupal::service('plugin.manager.mail')->mail('mcapi', 'mass', $to);

      //go to the transaction certificate
      $form_state->setRedirect(
        'entity.mcapi_transaction.canonical',
        ['mcapi_transaction' => $main_transaction->serial->value]
      );

      $this->logger('mcapi')->notice(
        'User @uid created @num mass transactions #@serial',
        [
          '@uid' => $this->currentUser()->id(),
          '@count' => count($form_state->get('validated_transactions')),
          '@serial' => $this->entity->serial->value
        ]
      );
    }
  }

  /**
   *
   * @param type $element
   * @param type $input
   * @param FormStateInterface $form_state
   * @return type
   * @deprecated
   */
  function form_type_select_wallets_value(&$element, $input, FormStateInterface $form_state) {
    if (empty($input)) {
      return;
    }
    foreach (explode(', ', $input) as $val) {
      $values[] = form_type_select_wallet_value($element, $val, $form_state);
    }
    return $values;
  }

}
