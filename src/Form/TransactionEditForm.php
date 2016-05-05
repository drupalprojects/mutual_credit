<?php

/**
 * @file
 * Definition of Drupal\mcapi\Form\TransactionEditForm.
 * Edit all the fields on a transaction
 */

namespace Drupal\mcapi\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\mcapi\Entity\Transaction;
use Symfony\Component\DependencyInjection\ContainerInterface;

class TransactionEditForm extends ContentEntityForm {

  /**
   * Constructs a ContentEntityForm object.
   *
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   * @param  $current_user
   * @param  $entity_query
   */
  public function __construct($entity_manager, $current_user, $entity_query) {
    $this->entityManager = $entity_manager;
    $this->currentUser = $current_user;
    $this->entityQuery = $entity_query;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.manager'),
      $container->get('current_user'),
      $container->get('entity.query')->get('mcapi_transaction')
    );
  }

  /**
   * Overrides Drupal\Core\Entity\EntityForm::form().
   */
  public function form(array $form, FormStateInterface $form_state) {

    $children = $this->entityQuery
      ->condition('parent', $this->entity->id())
      ->execute();
    if ($children) {
      drupal_set_message($this->t('Child transactions will be unaffected by changes to this transaction'), 'warning');
    }

    //the masspay form doesn't provide a transaction via the router or the paramConverter
    $transaction = $this->entity->getEntityTypeId() == 'mcapi_transaction'
      ? $this->entity
      : Transaction::Create();
    $form = parent::form($form, $form_state);

    $is_admin = $this->currentUser->hasPermission('manage mcapi');
    $form['type']['#access'] = $is_admin;
    $form['state']['#access'] = $is_admin;
    $form['creator']['#access'] = $is_admin;
    return $form;
  }

  /**
   * {@inheritdoc}
   *
   * @note does NOT call parent.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);//set $this->entity
    //now we divert to the transition confirm form
    $form_state->setRedirect(
      'entity.mcapi_transaction.canonical',
      ['mcapi_transaction' => $this->entity->serial->value]
    );
  }

}

