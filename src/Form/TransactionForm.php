<?php

/**
 * @file
 * Definition of Drupal\mcapi\Form\TransactionForm.
 * Edit all the fields on a transaction
 */

namespace Drupal\mcapi\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Entity\EntityManagerInterface;
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
    $form['creator'] = [
      '#title' => t('Creator'),
      '#description' => t("The user who logged this transaction"),
      '#type' => 'entity_autocomplete',
      '#target_type' => 'user',
      '#selection_handler' => 'default:user',
      '#selection_settings' => [],
      '#tags' => FALSE,
      '#default_value' => User::load(\Drupal::currentUser()->id()),
      '#weight' => 20
    ];
    $form = parent::form($form, $form_state);

    return $form;
  }

  /**
   * {@inheritdoc}
   * @note we are overriding here because this form is neither for saving nor deleting
   * and because previewing is compulsory. The created entitiy is passed to the
   * 'create' transition form where it is saved.
   */
  protected function actions(array $form, FormStateInterface $form_state) {
    return [
      'submit' => [
        '#type' => 'submit',
        '#value' => $this->t('Preview'),
        '#validate' => ['::validate'],
        '#submit' => ['::submitForm'],
      ]
    ];
  }

  /**
   * form validation callback
   * @todo remove typedDataValidated flag after beta10
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    //handles constraintValidation on all entity fields
    //parent::validate($form, $form_state);
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
   * {@inheritdoc}
   *
   * @param array $form
   * @param FormStateInterface $form_state
   *
   * @note does NOT call parent
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
    //is this the best place to be putting defaults? not in Transaction::precreate?
    if (array_key_exists('creator', $values) && $account = User::load($values['creator'])) {
      $entity->creator->target_id = $account->id();
    }
    else {
      $entity->creator->target_id = $this->currentUser()->id();//MUST be a logged in user!
    }
    return $entity;
  }

}

