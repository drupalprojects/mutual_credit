<?php

/**
 * @file
 * Contains \Drupal\mcapi\Form\ExchangeDeleteConfirm.
 */

namespace Drupal\mcapi\Form;

use Drupal\Core\Entity\EntityConfirmFormBase;

/**
 * Builds the form to delete a currency
 */
class ExchangeDeleteConfirm extends EntityConfirmFormBase {

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return t('Are you sure you want to delete %name?', array('%name' => $this->entity->label()));
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelRoute() {
    return array(
      'route_name' => 'mcapi.admin_exchange_list',
    );
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
  public function submit(array $form, array &$form_state) {
    $this->entity->delete();
    drupal_set_message(t('Exchange %label has been deleted.', array('%label' => $this->entity->label())));

    $form_state['redirect'] = 'admin/accounting/exchanges';
  }

}
