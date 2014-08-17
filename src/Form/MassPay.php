<?php

/**
 * @file
 * Definition of Drupal\mcapi\Form\MassPay.
 * Create an cluster of transactions, based on a single entity form
 * N.B. This could be an entity form for Transaction OR Exchange.
 * If the latter, it will be appropriately populated.
 */

namespace Drupal\mcapi\Form;

use Drupal\entity\Entity\EntityFormDisplay;
use Drupal\user\Entity\User;
use Drupal\mcapi\Entity\Transaction;
use Drupal\mcapi\Entity\Wallet;
use Drupal\Core\Form\FormStateInterface;

class MassPay extends TransactionForm {

  private $payers;
  private $payees;
  //private $exchange_id;
  //$this->entity refers to the Exchange until is changed in $this->form()

  public function getFormId() {
    return 'mcapi_mass_payment';
  }

  /**
   * Overrides Drupal\mcapi\Form\TransactionForm::form().
   */
  public function form(array $form, FormStateInterface $form_state) {

    if (empty($form_state->get('validated_transactions'))) {
      //$this->exchange_id = $this->entity;

      //we have to mimic some part of the normal entity form preparation.
      //TODO on the rebuilt form we need to make a default entity.
      //But how to get the submitted values from $form_state?
      //$form_state['input'] means before processing and
      //$form_state['values'] hasn't been calculated yet
      $this->entity = Transaction::create(array());

      $form_display = EntityFormDisplay::collectRenderDisplay($this->entity, 'mass');
      $this->setFormDisplay($form_display, $form_state);

      $form = parent::form($form, $form_state);
      $this->step_1($form, $form_state);
    }
    else {
      foreach ($form_state->get('validated_transactions') as $transaction) {
        $form['preview'][] = entity_view($transaction, 'sentence');
      }
    }
    return $form;
  }

  public function step_1(array &$form, FormStateInterface $form_state) {

    if (empty($form_state->get('confirmed'))) {
      $types = \Drupal::config('mcapi.wallets')->get('entity_types');
      if ($types['user:user'] == 1) {
        $mode_options = array(
          t('Only the users below'),
      	  t("All users' except the below"),
        );
      }
      else {
        $mode_options = array(
          t('Only the wallets below'),
          t("All wallets' except the below"),
        );
      }
      $form['mode'] = array(
      	'#title' => t('Mode'),
        '#description' => t('Determine how this form will work'),
        '#type' => 'radios',
        //TODO start with nothing selected to force the user to choose something.
        '#options' => $mode_options,
      );
      $form['one'] = $form['payer'];
      $form['payer']['#access'] = FALSE;
      $form['one']['#title'] = t('The one wallet');
      $form['direction'] = array(
        '#title' => t('Direction'),
        '#type' => 'radios',
        //TODO start with nothing selected to force the user to choose something.
        '#options' => array(
          'one2many' => t('One wallet pays many wallets'),
          'many2one' => t('One wallet charges many wallets')
        ),
      );

      $form['worth']['#required'] = TRUE;

      $form['many'] = $form['payee'];
      $form['payee']['#access'] = FALSE;
      $form['many']['#title'] = t('The many wallets');
      //modify the widget to return multiple values
      $form['many']['#value_callback'] = array($this, 'form_type_select_wallets_value');
      $form['mode']['#weight'] = 7;
      $form['one']['#weight'] = 8;
      $form['direction']['#weight'] = 9;
      $form['many']['#weight'] = 10;

      $form['type']['#type'] = 'value';
      $form['type']['#value'] = 'mass';

      //TODO
      $form['notification'] = array(
        '#title' => 'Notify everybody',
        //TODO decide whether to put rules in a different module
        '#description' => \Drupal::moduleHandler()->moduleExists('rules') ?
          $this->t('Ensure this mail does not clash with mails sent by the rules module.') : '',
      	'#type' => 'fieldset',
        '#open' => TRUE,
        '#weight' => 20,
        'body' => array(
      	  '#title' => $this->t('Message'),
          //TODO the tokens?
          //'#description' => $this->t('The following tokens are available:') .' [user:name]',
          '#type' => 'textarea',
          //this needs to be stored per-exchange. What's the best way?
          '#default_value' => \Drupal::service('user.data')->get('mcapi', \Drupal::currentUser()->id(), 'masspay')
        )
      );

    }
  }

  public function validate(array $form, FormStateInterface $form_state) {
    if ($form_state->getErrors()) return;
    //only validate step 1
    if (empty($form_state->get('validated_transactions'))) {
      form_state_values_clean($form_state);//without this, buildentity fails, but again, not so in nodeFormController

      $form_state->addValue('creator', \Drupal::currentUser()->id());
      $form_state->addValue('type', 'mass');
      $values = $form_state->getValues();
      if ($values['direction'] == 'one2many') {
        $payers = array($values['one']);
        $payees = $values['many'];
      }
      else {
        $payers = $values['many'];
        $payees = array($values['one']);
      }

      $template = Transaction::create($values);
      $uuid_service = \Drupal::service('uuid');
      foreach ($payers as $payer) {
        foreach ($payees as $payee) {
          if ($payer == $payee) {
            continue;
          }
          $next = clone $template;
          $next->payer = $payer;
          $next->payee = $payee;
          $next->uuid = $uuid_service->generate();
          $transactions[] = $next;
        }
      }
      //TODO after alpha12: handle transaction violations
      foreach ($transactions as $transaction) {
        $violations = $transaction->validate();
        foreach ($violations as $field => $message) {
          $form_state->setErrorByName($field, $message);
        }
      }
      //TODO update to d8
      //drupal_set_title(t('Are you sure?'));
      //TODO Should these / do these go in the temp store?
      $form_state->set('validated_transactions', $transactions);
      $form_state->set('wallets', array_unique(array_merge($payers, $payees)));
    }
  }


  public function submit(array $form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    //TODO how do we inject stuff into forms?
    \Drupal::service('user.data')->set('mcapi', \Drupal::currentUser()->id(), 'masspay', $values['notification']['body']);

    if (!array_key_exists('op', $values)) {
      $form_state->setRebuild(TRUE);
    }
    else {
      $main_transaction = array_shift($form_state->get('validated_transactions'));
      foreach ($form_state->get('validated_transactions') as $transaction) {
        $main_transaction->children[] = $transaction;
      }
      $main_transaction->save();

      //mail the owners of all the wallets involved.
      foreach (Wallet::loadMultiple($form_state->get('wallets')) as $wallet) {
        $uids[] = $wallet->user_id();
      }
      foreach (User::loadMultiple(array_unique($uids)) as $account) {
        $to[] = $account->getEmail();
      }
      //TODO make sure this is queueing
      //the mail body has been saved against the currentUser
      \Drupal::service('plugin.manager.mail')->mail('mcapi', 'mass', $to);

      //go to the transaction certificate
      $form_state->setRedirect(
        'mcapi.transaction_view',
        array(
          'mcapi_transaction' => $main_transaction->serial->value
        )
      );
    }
  }

  function form_type_select_wallets_value(&$element, $input, FormStateInterface $form_state) {
    if (empty($input)) return;
    foreach (explode(', ', $input) as $val) {
      $values[] = form_type_select_wallet_value($element, $val, $form_state);
    }
    return $values;
  }

}
