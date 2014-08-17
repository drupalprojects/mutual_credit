<?php

/**
 * @file
 * Contains \Drupal\mcapi_exchanges\Form\ExchangeDeleteConfirm.
 */

namespace Drupal\mcapi_exchanges\Form;

use Drupal\Core\Entity\ContentEntityConfirmFormBase;
use Drupal\Core\Url;
use Drupal\Core\Form\FormStateInterface;

/**
 * Builds the form to delete a currency
 */
class ExchangeDeleteConfirm extends ContentEntityConfirmFormBase {

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return t('Are you sure you want to delete %name?', array('%name' => $this->entity->label()));
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return new Url('mcapi.admin_exchange_list');
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

    $this->entity->delete();

    drupal_set_message(t("Exchange '%label' has been deleted.", array('%label' => $this->entity->label())));
    $form_state->setRedirect('mcapi.admin_exchange_list');
  }

}
