<?php

/**
 * @file
 * Contains \Drupal\firstparty_forms\Form\FirstPartyEditFormDisableConfirm.
 */

namespace Drupal\firstparty_forms\Form;

use Drupal\Core\Entity\EntityConfirmFormBase;

/**
 * Builds the form to delete a contact category.
 */
class FirstPartyEditFormDisableConfirm extends EntityConfirmFormBase {

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return t('Are you sure you want to disable %name?', array('%name' => $this->entity->label()));
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
    return t('Disable');
  }

  /**
   * {@inheritdoc}
   */
  public function submit(array $form, array &$form_state) {
    $this->entity->status = 0;
    $this->entity->save();
    drupal_set_message(t('"%label" has been disabled.', array('%label' => $this->entity->label())));
    $form_state['redirect'] = 'admin/accounting/workflow/forms';
  }

}
