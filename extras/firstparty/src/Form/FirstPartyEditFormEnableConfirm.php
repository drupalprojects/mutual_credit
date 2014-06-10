<?php

/**
 * @file
 * Contains \Drupal\mcapi_1stparty\Form\FirstPartyEditFormEnableConfirm.
 */

namespace Drupal\mcapi_1stparty\Form;

use Drupal\Core\Entity\EntityConfirmFormBase;

/**
 * Builds the form to delete a contact category.
 */
class FirstPartyEditFormEnableConfirm extends EntityConfirmFormBase {

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return t('Are you sure you want to enable %name?', array('%name' => $this->entity->label()));
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelRoute() {
    return array(
      'route_name' => 'mcapi.admin_1stparty_editform_list',
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return t('Enable');
  }

  /**
   * {@inheritdoc}
   */
  public function submit(array $form, array &$form_state) {
    $this->entity->status = 1;
    $this->entity->save();
    drupal_set_message(t('"%label" has been enabled.', array('%label' => $this->entity->label())));
    $form_state['redirect_route'] = array(
      'route_name' => 'mcapi.admin_1stparty_editform_list'
    );
  }

}
