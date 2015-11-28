<?php

/**
 * @file
 * Definition of Drupal\mcapi\Form\TransactionForm.
 * Edit all the fields on a transaction
 */

namespace Drupal\mcapi\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\user\Entity\User;
use Drupal\mcapi\Entity\Transaction;
use Symfony\Component\DependencyInjection\ContainerInterface;

class TransactionForm extends ContentEntityForm {

  private $tempstore;

  /**
   * 
   * @param Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   * @param \Drupal\user\PrivateTempStore $tempstore
   */
  public function __construct($entity_type_manager, $tempstore) {
    parent::__construct($entity_type_manager);
    $this->tempStore = $tempstore;
  }

  /**
   * {@inheritdoc}
   * @todo update to entity_type.manager
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
    $form = parent::form($form, $form_state);
    //the masspay form doesn't provide a transaction via the router or the paramConverter
    $transaction = $this->entity->getEntityTypeId() == 'mcapi_transaction'
      ? $this->entity
      : Transaction::Create();

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
    return $form;
  }

  /**
   * {@inheritdoc}
   * @note we are overriding here because this form is neither for saving nor deleting
   * and because previewing is compulsory. The created entitiy is passed to the
   * 'create' operation form where it is saved.
   */
  protected function actions(array $form, FormStateInterface $form_state) {
    return [
      'submit' => [
        '#type' => 'submit',
        '#value' => $this->t('Preview'),
        '#submit' => ['::submitForm'],//does NOT save()
      ]
    ];
  }

  /**
   * {@inheritdoc}
   * 
   * @note does NOT call parent.
   */
  final public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);//set $this->entity
    $this->tempStore
      ->get('TransactionForm')
      ->set('mcapi_transaction', $this->entity);
    //Drupal\mcapi\TransactionSerialConverter
    //then
    //Drupal\mcapi\Plugin\Transition\Create 
    //now we divert to the transition confirm form
    $form_state->setRedirect(
      'mcapi.transaction.operation',
      ['mcapi_transaction' => 0, 'operation' => 'save']);
  }

  /**
   * {@inheritdoc}
   * @todo test creating a transaction with and without specifying the creator
   */
  public function buildEntity(array $form, FormStateInterface $form_state) {
    $entity = parent::buildEntity($form, $form_state);
    //if a valid creator uid was submitted then use that
    //is this the best place to be putting defaults? not in Transaction::precreate?
    $uid = $form_state->getValue('creator') ? : \Drupal::currentUser()->id();
    $entity->creator->entity = user::load($uid);
    return $entity;
  }

}

