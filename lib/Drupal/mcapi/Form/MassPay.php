<?php

namespace Drupal\mcapi\Form;

use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Entity\EntityManager;
use Symfony\Component\DependencyInjection\ContainerInterface;


class MassPay extends FormBase {

  private $vector;

  function __construct() {
    $this->vector = 'many2one';
  }

  public function getFormId() {
    return 'mcapi_mass_payment';
  }

  /**
   * Overrides Drupal\Core\Entity\EntityFormController::form().
   */
  public function buildForm(array $form, array &$form_state) {
    echo 'Gordon - having real problems with the routing. We need 4 tabs here, passing different args to this form.';
    if (empty($form_state['validated_transactions'])) {
      $form = $this->step_1($form, $form_state);
      $form['#validate'][] = array($this, 'step_1_validate');
      $form['#submit'][] = array($this, 'step_1_submit');
    }
    else {
      $this->step_2($form, $form_state);
      //$form['#validate'][] = array($this, 'step_2_validate');
      //$form['#submit'][] = array($this, 'step_2_submit');
    }
    return $form;
  }

  public function step_1(array $form, array &$form_state) {

    list($payer, $payee) = explode('2', $this->vector);
    if (empty($form_state['confirmed'])) {

      $one = array(
        '#description' => t('A username, email, or user ID'),
        '#type' => 'user_chooser_few',
        '#callback' => 'user_chooser_segment_perms',
        '#args' => array('transact'),
        '#weight' => 9,
        '#multiple' => FALSE,
      );
      $few = array(
        '#type' => 'user_chooser_few',
        '#callback' => 'user_chooser_segment_perms',
        '#args' => array('transact'),
        '#multiple' => TRUE,
        '#multiple_fail_quietly' => TRUE
      );
      $many = array(
        '#type' => 'user_chooser_many',
      );

      if ($payer == 'one') {
        $form['payers'] = array(
          '#title' => t('Payer'),
          '#weight' => 1
        ) + $one;
        $form['payees'] = array(
          '#title' => t('Payees'),
          '#weight' => 2
        )+ $$payee;
      }
      elseif ($payee == 'one') {
        $form['payees'] = array(
          '#title' => t('Payee'),
          '#weight' => 1
        ) + $one;
        $form['payers'] = array(
          '#title' => t('Payers'),
          '#weight' => 2
        )+ $$payer;
      }

      //all these fields like on a normal transaction form
      $form['description'] = array(
        '#title' => t('Description'),
        '#type' => 'textfield',
        '#default_value' => ''
      );
      $form['worths'] = array(
        '#type' => 'worths',
        '#title' => t('Worth'),
        '#required' => TRUE,
      );
      $form['type'] = array(
        '#type'=> 'value',
        '#value' => 'mass'
      );
      $form['submit'] = array(
      	'#type' => 'submit',
        '#value' => $this->t('Preview'),
        '#weight' => 20
      );

      //add the remaining fieldAPI fields

      $entity = entity_create('mcapi_transaction', array());
      //TODO field attach form is deprecated although
      //public function EntityFormController::form still calls it
      //advice means nothing to me: "as of Drupal 8.0. Use the entity system instead."
      //so replace this...
      //field_attach_form($entity, $form, $form_state, $entity->language()->id);

      //TODO
      //Some way to optionally send mail
      /*    $form['mail']['#description'] = t('Do not use payer and payee tokens because all will receive the same mail.');
       $form['mail']['token_tree'] = array(
           '#theme' => 'token_tree',
           '#token_types' => array('transaction'),
           '#global_types' => FALSE,
           '#weight' => 3,
       );*/
    }
    return $form;
  }

  /*
   * create the transaction entities and validate them
   */
  public function step_1_validate(array $form, array &$form_state) {
    form_state_values_clean($form_state);//without this, buildentity fails, but again, not so in nodeFormController
    $values = &$form_state['values'];

    $values['creator'] = \Drupal::currentUser()->id();
    $values['type'] = 'mass';

    $payers = (array)$values['payers'];
    $payees = (array)$values['payees'];
    unset($values['payers'], $values['payees']);
    $template = entity_create('mcapi_transaction', $values);
    foreach ($payers as $payer) {
      foreach ($payees as $payee) {
        if ($payer == $payee) continue;
        $template->payer = $payer;
        $template->payee = $payee;
        $transactions[] = clone $template;
      }
    }
    foreach ($transactions as $transaction) {
      $transaction->validate();
      if (!empty($transaction->exceptions)) {
        $this->setErrorByName('Blah error');
      }
    }

    drupal_set_title(t('Are you sure?'));
    //Should these be a form property rather than being shoved in form_state?
    //What's best for memory management?
    //What if there are 2000 transactions?
    $form_state['validated_transactions'] = $transactions;
  }
  public function step_1_submit(array $form, array &$form_state) {
    $form_state['rebuild'] = TRUE;
  }

  public function step_2(&$form, $form_state) {
    //we have to preview these separately to see them all
    foreach ($form_state['validated_transactions'] as $transaction) {
      $form['preview'][]['#markup'] = $transaction->tokenised();
    }
    $form['submit'] = array(
    	'#type' => 'submit',
      '#value' => $this->t('Confirm'),
      '#weight' => 20
    );
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, array &$form_state, $op = NULL) {
    $main_transaction = array_shift($form_state['validated_transactions']);
    foreach ($form_state['validated_transactions'] as $transaction) {
      $main_transaction->children[] = $transaction;
    }
    $main_transaction->save();
    //print_r($parent);die();

    //redirect to the single user's page.
    $uri = $main_transaction->uri();
    $form_state['redirect'] = $uri['path'];//might not be a good idea for undone transactions

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
      $xids = array_keys(transaction_filter(array('serial' => $serial)));
      //TODO batch this
      foreach (entity_load('mcapi_transaction', $xids) as $transaction) {
        $params['transaction'] = $transaction;
        $account = user_load($transaction->$mailto);
        drupal_mail('accountant_ui', 'mass', $account->mail, user_preferred_language($account, $language), $params);
      }
    }
    */
  }
}