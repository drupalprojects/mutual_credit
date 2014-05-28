<?php

/**
 * @file
 * Contains \Drupal\mcapi\Form\ExchangeActivateConfirm.
 */

namespace Drupal\mcapi\Form;

use Drupal\Core\Entity\ContentEntityConfirmFormBase;

/**
 * Builds the form to delete a currency
 */
class ExchangeActivateConfirm extends ContentEntityConfirmFormBase {

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return t('Are you sure you want to open %name for trading?', array('%name' => $this->entity->label()));
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
    return t('Active');
  }

  /**
   * {@inheritdoc}
   */
  public function submit(array $form, array &$form_state) {

    $this->entity->set('status', TRUE);
    $this->entity->save();

    drupal_set_message(t("Exchange '%label' is now active.", array('%label' => $this->entity->label())));
    $form_state['redirect_route'] = array(
      'route_name' => 'mcapi.admin_exchange_list'
    );
  }

}
