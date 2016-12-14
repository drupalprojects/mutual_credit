<?php

namespace Drupal\mcapi\Plugin\Action;

use Drupal\Core\Form\FormStateInterface;

/**
 * Action which leads to the entity edit form.
 *
 * @Action(
 *   id = "mcapi_transaction.edit_period_action",
 *   label = @Translation("Edit a transaction (time limited)"),
 *   type = "mcapi_transaction",
 *   confirm_form_route_name = "entity.mcapi_transaction.edit_form"
 * )
 */
class EditPeriod extends Edit {

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {

  }


  /**
   * {@inheritdoc}
   */
  public function execute($object = NULL) {

  }

}
