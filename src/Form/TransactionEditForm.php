<?php

/**
 * @file
 * Definition of Drupal\mcapi\Form\TransactionEditForm.
 * Edit all the fields on a transaction
 */

namespace Drupal\mcapi\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\user\Entity\User;
use Drupal\mcapi\Entity\Transaction;
//use Symfony\Component\DependencyInjection\ContainerInterface;

class TransactionEditForm extends ContentEntityForm {

  /**
   * Overrides Drupal\Core\Entity\EntityForm::form().
   */
  public function form(array $form, FormStateInterface $form_state) {

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
    $form = parent::form($form, $form_state);
    return $form;
  }

  /**
   * {@inheritdoc}
   * 
   * @note does NOT call parent.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    drupal_set_message("editing transactions hasn't been coded yet");
    parent::submitForm($form, $form_state);//set $this->entity

    //now we divert to the transition confirm form
    $form_state->setRedirect(
      'entity.mcapi_transaction.canonical',
      ['mcapi_transaction' => $this->entity->id()]
    );
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

