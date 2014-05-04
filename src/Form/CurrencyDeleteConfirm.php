<?php

/**
 * @file
 * Contains \Drupal\mcapi\Form\CurrencyDeleteForm.
 */

namespace Drupal\mcapi\Form;

use Drupal\Core\Entity\EntityConfirmFormBase;

/**
 * Builds the form to delete a currency
 */
class CurrencyDeleteConfirm extends EntityConfirmFormBase {

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return t('Are you sure you want to delete %name? ALL transactions in that currency will be deleted!', array('%name' => $this->entity->label()));
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
    return t('Delete');
  }

  /**
   * {@inheritdoc}
   */
  public function submit(array $form, array &$form_state) {
    //delete all the transactions in this currency.
    \Drupal::EntityManager()->getStorage('mcapi_transaction')->currencyDelete($this->entity->id());
    $this->entity->delete();
    drupal_set_message(t('Currency %label has been deleted.', array('%label' => $this->entity->label())));
    $form_state['redirect_route'] = array(
      'route_name' => 'mcapi.admin_currency_list'
    );
  }

}
