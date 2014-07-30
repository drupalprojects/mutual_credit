<?php

/**
 * @file
 * Definition of Drupal\mcapi\Form\TransactionForm.
 * Edit all the fields on a transaction
 *
 * We have to start by working out the what exchange this transaction
 * is happening in. This helps us to work out what currencies to show
 * and what users to make available in the user selection widgets
 */

namespace Drupal\mcapi\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\mcapi\ViewBuilder\TransactionViewBuilder;
use Drupal\mcapi\McapiTransactionException;
use Drupal\action\Plugin\Action;
use Drupal\Core\Template\Attribute;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\mcapi\Entity\Transaction;

class TransactionForm extends ContentEntityForm {

  /**
   * Overrides Drupal\Core\Entity\EntityFormController::form().
   */
  public function form(array $form, array &$form_state) {

    //the masspay form doesn't provide a transaction via the router or the paramConverter
    $transaction = $this->entity->getEntityTypeId() == 'mcapi_transaction'
      ? $this->entity
      : Transaction::Create();


    //TODO do this with access control, including the dsm
    if (count(referenced_exchanges(NULL, TRUE)) < 1) {
      drupal_set_message(t('You are not a member of any exchanges, so you cannot trade with anyone'));
      $form['#disabled'] = TRUE;
    }

    //borrowed from NodeFormController::prepareEntity
    $transaction->date = format_date($transaction->created->value, 'custom', 'Y-m-d H:i:s O');
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
      //the empty class causes an error in alpha11  _form_set_attributes()
      '#attributes' => new Attribute(
        array(
          'style' => "width:100%",
          'class' => array()
        )
      ),
      //TEMP the old way
      '#attributes' => array('style' => 'width:100%', 'class' => array())
    );

    //lists all the wallets in the exchange
    $form['payer'] = array(
      '#title' => t('Wallet to be debited'),
      '#type' => 'select_wallet',
      '#local' => TRUE,
      '#default_value' => $transaction->get('payer')->value,
      '#weight' => 9,
    );
    $form['payee'] = array(
      '#title' => t('Wallet to be credited'),
      '#type' => 'select_wallet',
      '#local' => TRUE,
      '#default_value' => $transaction->get('payee')->value,
      '#weight' => 9,
    );
    //direct copy from the node module, but what about the datetime field?
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
      '#access' => user_access('manage own exchanges'),
    );
    if (module_exists('datetime')) {
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
   * form validation callback
   * I can't imagine why, but this is called twice when the form is submitted
   * since validation is an intensive process, perhaps we need a #mcapi_validated flag?
   *
   * this is unusual because normally build a temp object
   */
  public function validate(array $form, array &$form_state) {

    form_state_values_clean($form_state);//without this, buildentity fails, but not so in nodeFormController

    $transaction = $this->buildEntity($form, $form_state);

    if (!$transaction->created->value) {
      $transaction->set('created', REQUEST_TIME);
    }
    // The date element contains the date object.
    $date = $transaction->created instanceof DrupalDateTime ? $transaction->created : new DrupalDateTime($transaction->created->value);
    if ($date->hasErrors()) {
      $this->setFormError('created', $form_state, $this->t('You have to specify a valid date.'));
    }
    if (!$transaction->creator->value) {
      $transaction->set('creator', \Drupal::currentUser()->id());
    }

    //make sure we're not running this twice
    if (array_key_exists('mcapi_validated', $form_state)){'running form validation twice';return;}
    else $form_state['mcapi_validated'] = TRUE;

    //validate the fieldAPI widgets
    $this->getFormDisplay($form_state)->validateFormValues($transaction, $form, $form_state);

    //TODO take this out of the global scope
    if (\Drupal::formBuilder()->getErrors($form_state)) {
      return;
    }


    //node_form controller runs a hook for validating the node
    //however we do it here IN the transaction entity validation which is less form-dependent

    try {
      //curiously, I can't find an instance of the entity->validate() being called. I think it might be new in alpha 11
      if ($violations = $transaction->validate()) {
        //TEMP this throws just one
        foreach ($violations as $field => $message) {
          throw new McapiTransactionException($field, $message);
        }
      }
      $child_errors = \Drupal::config('mcapi.misc')->get('child_errors');
      foreach ($transaction->warnings as $message) {
        if (!$child_errors['allow']) {
          $this->setFormError(key($message), $form_state, $message);
        }
        elseif ($child_errors['show_messages']) {
          drupal_set_message($e->getMessage, 'warning');
        }
      }
      //now validated, this is what we will put in the tempstore
      $this->entity = $transaction;
    }
    catch (McapiTransactionException $e) {
      //The Exception message may have several error messages joined together

      $this->setFormError($e->getField(), $form_state, $e->getMessage());
    }
  }

  public function submit(array $form, array &$form_state) {
    $tempStore = \Drupal::service('user.tempstore');
    $tempStore->get('TransactionForm')->set('entity', $this->entity);
    //Drupal\mcapi\ParamConverter\TransactionSerialConverter
    //then
    //Drupal\mcapi\Plugin\Transition\Create

    //now we divert to the transition confirm form
    $form_state['redirect'] = 'transaction/0/create';
  }

}

