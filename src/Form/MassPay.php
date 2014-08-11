<?php

/**
 * @file
 * Definition of Drupal\mcapi\Form\MassPay.
 * Create an array of transactions, based on a single entity form
 */

namespace Drupal\mcapi\Form;

use Drupal\entity\Entity\EntityFormDisplay;
use Drupal\user\Entity\User;
use Drupal\mcapi\Entity\Transaction;
use Drupal\Core\Form\FormStateInterface;

class MassPay extends TransactionForm {

  private $payers;
  private $payees;
  private $exchange_id;
  //$this->entity refers to the Exchange

  public function getFormId() {
    return 'mcapi_mass_payment';
  }

  /**
   * Overrides Drupal\mcapi\Form\TransactionForm::form().
   */
  public function form(array $form, FormStateInterface $form_state) {

    if (empty($form_state->get('validated_transactions'))) {
      $this->exchange_id = $this->entity;

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
      /*
      $form['mail']['#description'] = t('Do not use payer and payee tokens because all will receive the same mail.');
      //this presumes the existence of the token module in d7
      $form['mail']['token_tree'] = array(
        '#theme' => 'token_tree',
        '#token_types' => array('mcapi'),
        '#global_types' => FALSE,
        '#weight' => 3,
      );
      */
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
          if ($payer == $payee) continue;
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
    }
  }


  public function submit(array $form, FormStateInterface $form_state) {
    if (!array_key_exists('op', $form_state->getValues('values'))) {
      $form_state->setRebuild(TRUE);
    }
    else {
      $main_transaction = array_shift($form_state->get('validated_transactions'));
      foreach ($form_state->get('validated_transactions') as $transaction) {
        $main_transaction->children[] = $transaction;
      }
      $main_transaction->save();

      $form_state->setRedirect(
        'mcapi.transaction_view',
        array(
          'mcapi_transaction' => $main_transaction->serial->value
        )
      );
    }
  }


  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state, $op = NULL) {


    //store the mail so we can bring it up as a default next time
    //if there was an $op called 'create' we could store this mail in the op settings.
    /*
    variable_set('mcapi_accounting_masspay_mail', array(
    'subject' => $form_state['storage']['mcapi_accounting_masspay_subject'],
    'body' => $form_state['storage']['mcapi_accounting_masspay_body'],
    ));
    $params = variable_get('mcapi_accounting_masspay_mail');

    if (strlen($params['subject']) && strlen($params['body'])) {
      $mailto = count($form_state['values']['payees']) > 1 ? 'payee' : 'payer';
      global $language;
      $xids = array_keys(\Drupal\mcapi\Storage\TransactionStorage::filter(array('serial' => $serial)));
      //TODO batch this
      foreach (entity_load('mcapi_transaction', $xids) as $transaction) {
        $params['transaction'] = $transaction;
        $account = User::load($transaction->$mailto);
        drupal_mail('accountant_ui', 'mass', $account->mail, user_preferred_language($account, $language), $params);
      }
    }
    */
  }

  function form_type_select_wallets_value(&$element, $input, FormStateInterface $form_state) {
    if (empty($input)) return;
    foreach (explode(', ', $input) as $val) {
      $values[] = form_type_select_wallet_value($element, $val, $form_state);
    }
    return $values;
  }

}
