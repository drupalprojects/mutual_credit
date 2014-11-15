<?php

/**
 * @file
 * Contains \Drupal\mcapi_1stparty\Form\FirstPartyEditFormDisableConfirm.
 */

namespace Drupal\mcapi_1stparty\Form;

use Drupal\Core\Entity\EntityConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Builds the form to disable a firstparty transaction form.
 */
class FirstPartyEditFormDisableConfirm extends EntityConfirmFormBase {

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to disable %name?', array('%name' => $this->entity->label()));
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return new Url('mcapi.admin_1stparty_editform_list');
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return $this->t('Disable');
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->entity->status = 0;
    $this->entity->save();
    drupal_set_message($this->t('Form "%label" has been disabled.', array('%label' => $this->entity->label())));
    $form_state->setRedirectUrl($this->getCancelUrl());
  }

}
