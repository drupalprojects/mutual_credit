<?php

/**
 * @file
 * Contains \Drupal\mcapi\Form\CurrencyDisableConfirm.
 */

namespace Drupal\mcapi\Form;

use Drupal\Core\Entity\EntityConfirmFormBase;

/**
 * Builds the form to delete a currency.
 */
class CurrencyDisableConfirm extends EntityConfirmFormBase {

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
      'route_name' => 'mcapi.admin_currency_list',
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
    \Drupal::cache()->deleteTags(array('mcapi.available_currency'));
    drupal_set_message(t('Currency %label has been disabled.', array('%label' => $this->entity->label())));
    $form_state['redirect_route'] = array(
      'route_name' => 'mcapi.admin_currency_list'
    );
  }

}
