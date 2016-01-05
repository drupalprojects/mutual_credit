<?php

/**
 * @file
 * Contains \Drupal\mcapi_exchanges\Form\ExchangeEnableConfirm.
 */

namespace Drupal\mcapi_exchanges\Form;

use Drupal\Core\Entity\ContentEntityConfirmFormBase;
use Drupal\Core\Url;
use Drupal\Core\Form\FormStateInterface;

/**
 * Builds the form to delete a currency
 */
class ExchangeEnableConfirm extends ContentEntityConfirmFormBase {

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return t('Are you sure you want to open %name for trading?', array('%name' => $this->entity->label()));
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
    return t('Active');
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, \Drupal\Core\Form\FormStateInterface $form_state) {

    $this->entity->set('status', TRUE);
    $this->entity->save();

    drupal_set_message(t("Exchange '%label' is now active.", array('%label' => $this->entity->label())));
    $form_state->setRedirectUrl($this->getCancelUrl());
  }

}
