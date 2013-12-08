<?php

/**
 * @file
 * Contains \Drupal\mcapi\Form\CurrencyEnableConfirm.
 */

namespace Drupal\mcapi\Form;

use Drupal\Core\Entity\EntityConfirmFormBase;

/**
 * Builds the form to delete a contact category.
 */
class CurrencyEnableConfirm extends EntityConfirmFormBase {

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
      'route_name' => 'mcapi.admin_currency_list',
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
    \Drupal::cache()->deleteTags(array('mcapi.available_currency'));
    drupal_set_message(t('Currency %label has been enabled.', array('%label' => $this->entity->label())));
    $form_state['redirect'] = 'admin/accounting/currencies';
  }

}
