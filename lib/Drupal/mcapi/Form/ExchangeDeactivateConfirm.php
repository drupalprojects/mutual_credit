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

    //remove all references to this exchange in entity_reference
    module_load_include('inc', 'mcapi');
    foreach (get_exchange_entity_fieldnames() as $entity_type => $field_name) {
      //@todo Delete the references to this entity
      drupal_set_message('Any references to this exchange have not been changed. This may produce warning messages', 'warning');
    }

    $this->entity->set('active', FALSE);
    $this->entity->save();

    drupal_set_message(t("Exchange '%label' is deactivated.", array('%label' => $this->entity->label())));
    $form_state['redirect_route'] = array(
      'route_name' => 'mcapi.admin_exchange_list'
    );
  }

}
