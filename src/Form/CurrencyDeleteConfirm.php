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
    return t('Are you sure you want to delete %name? ALL transactions in that currency will be deleted!', array('%name' => $this->entity->label()));
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    //want to go back to the list builder but its not normal to put the list in the entity->links property
    return new Url('mcapi.admin_currency_list');
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return t('Delete');
  }

  /**
   * {@inheritdoc}
   */
  public function submit(array $form, FormStateInterface $form_state) {
    \Drupal::EntityManager()->getStorage('mcapi_transaction')->wipeslate($this->entity->id());
    $this->entity->delete();
    drupal_set_message(t('Currency %label has been deleted.', array('%label' => $this->entity->label())));
    $form_state->setRedirect('mcapi.admin_currency_list');
  }

}
