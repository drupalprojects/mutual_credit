<?php

namespace Drupal\mcapi_exchanges\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Entity\ContentEntityConfirmFormBase;
use Drupal\Core\Url;

/**
 * Builds the form to delete a currency.
 */
class ExchangeDisableConfirm extends ContentEntityConfirmFormBase {

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return t('Are you sure you want to deactivate %name?', array('%name' => $this->entity->label()));
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
    return t('Deactivate');
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->entity->set('status', FALSE);
    $this->entity->save();

    drupal_set_message(t("Exchange '%label' is deactivated.", array('%label' => $this->entity->label())));
    $form_state->setRedirectUrl($this->getCancelUrl());
  }

}
