<?php

/**
 * @file
 * Contains \Drupal\mcapi\Form\TransactionActionEditForm.
 */

namespace Drupal\mcapi\Form;
use Drupal\Core\Form\FormStateInterface;


/**
 * Provides a form for action edit forms.
 */
class TransactionActionEditForm extends \Drupal\action\ActionEditForm {

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    parent::save($form, $form_state);
    $form_state->setRedirect('mcapi.admin.workflow');
  }

}
