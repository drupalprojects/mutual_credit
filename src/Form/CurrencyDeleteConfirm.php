<?php

/**
 * @file
 * Contains \Drupal\mcapi\Form\CurrencyDeleteForm.
 */

namespace Drupal\mcapi\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Entity\EntityDeleteForm;

/**
 * Builds the form to delete a currency
 */
class CurrencyDeleteConfirm extends EntityDeleteForm {

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->t('ALL transactions in that currency will be deleted!');
  }


  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);
    \Drupal::entityTypeManager()->getStorage('mcapi_transaction')->wipeslate($this->entity->id());
  }

}
