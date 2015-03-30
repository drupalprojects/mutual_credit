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
use Symfony\Component\DependencyInjection\ContainerInterface;

class TransactionForm extends ContentEntityForm {

  public $tempstore;
  
  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $static = parent::create($container);
    //this saves overriding the __construct method at a cost of making $tempstore public
    $static->tempstore = $container->get('user.private_tempstore');
    return $static;
  }
  
  /**
   * Overrides Drupal\Core\Entity\EntityForm::form().
   */
  public function form(array $form, FormStateInterface $form_state) {

    //the masspay form doesn't provide a transaction via the router or the paramConverter
    $transaction = $this->entity->getEntityTypeId() == 'mcapi_transaction'
      ? $this->entity
      : Transaction::Create();

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
      '#role' => 'payer',
      '#default_value' => $transaction->get('payer')->target_id,
      '#weight' => 9,
    );
    $form['payee'] = array(
      '#title' => t('Wallet to be credited'),
      '#type' => 'select_wallet',
      '#role' => 'payee',
      '#default_value' => $transaction->get('payee')->target_id,
      '#weight' => 9,
    );

    $form['type'] = array(
      '#title' => t('Transaction type'),
      '#options' => mcapi_entity_label_list('mcapi_type'),
      '#type' => 'mcapi_types',
      '#default_value' => $transaction->get('type')->value,
      '#required' => TRUE,
      '#weight' => 18,
    );
    
    return parent::form($form, $form_state);
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
    //runs the form validation handlers and 
    //runs datatype->validate() on all shown fields.
    parent::validate($form, $form_state);
    $this->typedDataValidated = TRUE;//temp flag
    
    $transaction = $this->buildEntity($form, $form_state);
    $exceptions = $transaction->validate();
    foreach ($exceptions as $mcapi_exception) {
      $form_state->setErrorByName($mcapi_exception->getField(), $mcapi_exception->getMessage());
    }
    //show the warnings
    $child_errors = \Drupal::config('mcapi.misc')->get('child_errors');
    foreach ($transaction->warnings as $e) {
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
    \Drupal::service('user.private_tempstore')
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
    
    $values = $form_state->getValues();
    //if a valid creator uid was submitted then use that
    if (array_key_exists('creator', $values) && $account = User::load($values['creator'])) {
      $entity->creator->target_id = $account->id();
    }
    else {
      $entity->creator->target_id = \Drupal::currentUser()->id();//MUST be a logged in user!
    }
    if (!empty($values['created'])) {
      //$entity->set('created', $values['created']);
    }
    return $entity;
  }

}

