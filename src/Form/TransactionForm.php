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

  private $tempstore;

  public function __construct(EntityManagerInterface $entity_manager, $tempstore) {
    parent::__construct($entity_manager);
    $this->tempStore = $tempstore;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.manager'),
      $container->get('user.private_tempstore')
    );
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

    $form['description'] = [
      '#type' => 'textfield',
      '#title' => t('Description'),
      '#default_value' => $transaction->description->value,
      '#weight' => 3
    ];
    $form['payer'] = [
      '#title' => t('Wallet to be debited'),
      '#type' => 'select_wallet',
      '#role' => 'payer',
      '#default_value' => $transaction->get('payer')->entity,
      '#weight' => 9,
    ];
    $form['payee'] = [
      '#title' => t('Wallet to be credited'),
      '#type' => 'select_wallet',
      '#role' => 'payee',
      '#default_value' => $transaction->get('payee')->entity,
      '#weight' => 10,
    ];
    $form['type'] = [
      '#title' => t('Transaction type'),
      '#options' => mcapi_entity_label_list('mcapi_type'),
      '#type' => 'mcapi_types',
      '#default_value' => $transaction->get('type')->target_id,
      '#required' => TRUE,
      '#weight' => 18,
    ];
    return parent::form($form, $form_state);
  }

  /**
   * Returns the action form element for the current entity form.
   */
  protected function actionsElement(array $form, FormStateInterface $form_state) {
    $actions = parent::actionsElement($form, $form_state);
    $actions['submit']['#submit'] = ['::submitForm'];
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
    //handles constraintValidation on all entity fields
    parent::validate($form, $form_state);
    $this->typedDataValidated = TRUE;//@todo remove this flag when entity validation set up

    $this->entity = $this->buildEntity($form, $form_state);
    //@todo improve transaction validation compare with nodeForm & display
    foreach ($this->entity->validate() as $violation) {
      $form_state->setErrorByName('', $violation->getMessage());
    }
    //now validated, this is what will go in the tempstore
    $form_state->set('mcapi_validated', TRUE);

  }

  /**
   * submit handler specified in EntityForm::actions
   * does NOT call parent
   * @param array $form
   * @param FormStateInterface $form_state
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->tempStore
      ->get('TransactionForm')
      ->set('entity', $this->entity);
    //Drupal\mcapi\ParamConverter\TransactionSerialConverter
    //then
    //Drupal\mcapi\Plugin\Transition\Create

    $this->logger('mcapi')->notice(
      'User @uid created transaction @serial',
      [
        '@uid' => $this->currentUser()->id(),
        '@serial' => $this->entity->serial->value
      ]
    );
    drupal_set_message('Check this transaction is in the dblog');

    //now we divert to the transition confirm form
    $form_state->setRedirect(
      'mcapi.transaction.op',
      ['mcapi_transaction' => 0, 'transition' => 'create']);
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
      $entity->creator->target_id = $this->currentUser()->id();//MUST be a logged in user!
    }
    if (!empty($values['created'])) {
      //$entity->set('created', $values['created']);
    }
    return $entity;
  }

}

