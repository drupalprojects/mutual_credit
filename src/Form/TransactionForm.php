<?php

/**
 * @file
 * Definition of Drupal\mcapi\Form\TransactionForm.
 * Edit all the fields on a transaction
 */

namespace Drupal\mcapi\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Template\Attribute;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\action\Plugin\Action;
use Drupal\user\Entity\User;
use Drupal\mcapi\ViewBuilder\TransactionViewBuilder;
use Drupal\mcapi\McapiTransactionException;
use Drupal\mcapi\Entity\Transaction;
use Drupal\mcapi\Exchanges;

class TransactionForm extends ContentEntityForm {

  /**
   * Overrides Drupal\Core\Entity\EntityForm::form().
   */
  public function form(array $form, FormStateInterface $form_state) {

    //the masspay form doesn't provide a transaction via the router or the paramConverter
    $transaction = $this->entity->getEntityTypeId() == 'mcapi_transaction'
      ? $this->entity
      : Transaction::Create();

    //TODO do this with access control, including the dsm
    if (!Exchanges::in(NULL, TRUE)) {
      drupal_set_message(t('You are not a member of any exchanges, so you cannot trade with anyone'));
      $form['#disabled'] = TRUE;
    }

    //borrowed from NodeForm::prepareEntity in alpha14
    $transaction->date = format_date($transaction->created->value, 'custom', 'Y-m-d H:i:s O');
    //but this looks better to me
    //$transaction->date = DrupalDateTime::createFromTimestamp($transaction->created->value);

    //the actual exchange that the transaction takes place in
    //will be determined automatically, once we know who is involved and what currencies.
    //in most use cases only one will be possible or likely
    //until then we offer a choice of users and currencies
    //from all the exchanges the current user is a member of

    unset($form['langcode']); //No language so we remove it.

    $form['description'] = array(
      '#type' => 'textfield',
      '#title' => t('Description'),
      '#default_value' => $transaction->description->value,
      '#weight' => 3,
      '#attributes' => new Attribute(
        array(
          'style' => "width:100%",
          'class' => []
        )
      ),
      //TODO TEMP
      '#attributes' => array(
        'style' => "width:100%",
        'class' => []
      )
    );

    //lists all the wallets in the exchange
    $form['payer'] = array(
      '#title' => t('Wallet to be debited'),
      '#type' => 'select_wallet',
      '#default_value' => $transaction->get('payer')->target_id,
      '#weight' => 9,
    );
    $form['payee'] = array(
      '#title' => t('Wallet to be credited'),
      '#type' => 'select_wallet',
      '#default_value' => $transaction->get('payee')->target_id,
      '#weight' => 9,
    );
    //direct copy from the node module, but what about the datetime field?
    //see datetime_form_node_form_alter
    $form['created'] = array(
      '#type' => 'textfield',
      '#title' => t('Entered on'),
      '#maxlength' => 25,
      '#description' => t(
        'Format: %time. The date format is YYYY-MM-DD and %timezone is the time zone offset from UTC. Leave blank to use the time of form submission.',
        array(
          '%time' => !empty($transaction->date)
            ? date_format(date_create($transaction->date), 'Y-m-d H:i:s O')
            : format_date($transaction->get('created')->value, 'custom', 'Y-m-d H:i:s O'),
          '%timezone' => !empty($transaction->created)
            ? date_format(date_create($transaction->date), 'O')
            : format_date($transaction->created->value, 'custom', 'O')
          )
        ),
      '#default_value' => !empty($transaction->date) ? $transaction->date : '',
      '#access' => $this->currentUser()->hasPermission('manage mcapi'),
    );
    if (\Drupal::moduleHandler()->moduleExists('datetime')) {
      //improve the date widget, which by a startling coincidence is called 'created' in the node form as well.
      //datetime_form_node_form_alter($form, $form_state, NULL);
    }
    $form['type'] = array(
      '#title' => t('Transaction type'),
      '#options' => mcapi_entity_label_list('mcapi_type'),
      '#type' => 'mcapi_types',
      '#default_value' => $transaction->get('type')->value,
      '#required' => TRUE,
      '#weight' => 18,
    );
    
    $form = parent::form($form, $form_state);

    return $form;
  }

  /**
   * Returns the action form element for the current entity form.
   */
  protected function actionsElement(array $form, FormStateInterface $form_state) {
    $actions = parent::actionsElement($form, $form_state);
    $actions['submit']['#submit'] = array('::submitForm');
    return $actions;
  }

  /**
   * form validation callback
   * I can't imagine why, but this is called twice when the form is submitted
   * since validation is an intensive process, perhaps we need a #mcapi_validated flag?
   *
   * this is unusual because normally build a temp object
   */
  public function validate(array $form, FormStateInterface $form_state) {
    //$form_state->cleanValues();;//without this, buildentity fails, but not so in nodeForm

    $transaction = $this->buildEntity($form, $form_state);
    
    // The date element contains the date object.
    $date = $transaction->created instanceof DrupalDateTime
      ? $transaction->created->value
      : new DrupalDateTime($transaction->created->value);
    
    //TODO there was a problem creating the date in alpha14
    if ($date->hasErrors()) {
      $message = $this->t('You have to specify a valid date.');
      //$form_state->setErrorByName('created', 'MCAPI:'.$message);
    }
    if (!$transaction->creator->target_id) {
      $transaction->set('creator', \Drupal::currentUser()->id());
    }

    
    parent::validate($form, $form_state);
    
    //node_form controller runs a hook for validating the node
    //however we do it here IN the transaction entity validation which is less form-dependent

    //validate the fieldAPI widgets
    //$this->getFormDisplay($form_state)
      //->validateFormValues($transaction, $form, $form_state);

    //if there are errors at the form level don't bother validating the entity object
    if ($form_state->getErrors()) {
      return;
    }

    //curiously, I can't find an instance of the entity->validate() being called. I think it might be new in alpha 11
    //if ($violations = $transaction->validate()) {
    //  foreach ($violations as $field => $message) {
    //    $form_state->setErrorByName($field, $message);
    //  }
    //}
    //show the warnings
    $child_errors = \Drupal::config('mcapi.misc')->get('child_errors');
    foreach ($transaction->warnings as $message) {
      if ($child_errors['show_messages']) {
        drupal_set_message($e->getMessage, 'warning');
      }
    }
    //now validated, this is what will go in the tempstore
    $this->entity = $transaction;
    $form_state->set('mcapi_validated', TRUE);

  }

  /**
   * submit handler specified in EntityForm::actions
   * does NOT call parent
   * @param array $form
   * @param FormStateInterface $form_state
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    //TODO inject this
    \Drupal::service('user.tempstore')
      ->get('TransactionForm')
      ->set('entity', $this->entity);
    //Drupal\mcapi\ParamConverter\TransactionSerialConverter
    //then
    //Drupal\mcapi\Plugin\Transition\Create

    //now we divert to the transition confirm form
    $form_state->setRedirect('mcapi.transaction.op', array('mcapi_transaction' => 0, 'transition' => 'create'));
  }

  /**
   * {@inheritdoc}
   */
  public function buildEntity(array $form, FormStateInterface $form_state) {
    $entity = parent::buildEntity($form, $form_state);
    $entity->creator->target_id = \Drupal::currentUser()->id();//MUST be a logged in user!
    $entity->set('created', REQUEST_TIME);
    
    $values = $form_state->getValues();
    if (array_key_exists('creator', $values) && $account = User::load($values['creator'])) {
      $entity->creator->target_id = $account->id();
    }
    if (!empty($values['created']) && $values['created'] instanceOf DrupalDateTime) {
      $entity->set('created', $values['created']->getTimestamp());
    }
    return $entity;
  }

}

