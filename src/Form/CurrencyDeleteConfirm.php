<?php

/**
 * @file
 * Contains \Drupal\mcapi\Form\CurrencyDeleteForm.
 */

namespace Drupal\mcapi\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Entity\EntityConfirmFormBase;
use Drupal\Core\Url;

/**
 * Builds the form to delete a currency
 */
class CurrencyDeleteConfirm extends EntityConfirmFormBase {

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to delete %name?', array('%name' => $this->entity->label()));
  }
  
  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->t('ALL transactions in that currency will be deleted!');
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return new Url('mcapi.admin_currency_list');
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return $this->t('Delete');
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    \Drupal::EntityManager()->getStorage('mcapi_transaction')->wipeslate($this->entity->id());
    drupal_set_message($this->t('Currency %label has been deleted.', array('%label' => $this->entity->label())));
    $this->entity->delete();
    $form_state->setRedirectUrl($this->getCancelUrl());
  }

}
