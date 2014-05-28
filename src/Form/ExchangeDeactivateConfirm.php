<?php

/**
 * @file
 * Contains \Drupal\mcapi\Form\ExchangeDeactivateConfirm.
 */

namespace Drupal\mcapi\Form;

use Drupal\Core\Entity\ContentEntityConfirmFormBase;

/**
 * Builds the form to delete a currency
 */
class ExchangeDeactivateConfirm extends ContentEntityConfirmFormBase {

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return t('Are you sure you want to deactivate %name?', array('%name' => $this->entity->label()));
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
    return t('Deactivate');
  }

  /**
   * {@inheritdoc}
   */
  public function submit(array $form, array &$form_state) {
    $this->entity->set('status', FALSE);
    $this->entity->save();

    drupal_set_message(t("Exchange '%label' is deactivated.", array('%label' => $this->entity->label())));
    $form_state['redirect_route'] = array(
      'route_name' => 'mcapi.admin_exchange_list'
    );
  }

}
