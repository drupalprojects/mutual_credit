<?php

/**
 * @file
 *  Contains Drupal\mcapi\Plugin\Action\Delete
 */

namespace Drupal\mcapi\Plugin\Action;

use Drupal\Core\Form\FormStateInterface;


/**
 * Removes a transaction from the db.
 *
 * @Action(
 *   id = "mcapi_transaction.delete_action",
 *   label = @Translation("Delete a transaction"),
 *   type = "mcapi_transaction",
 *   confirm_form_route_name = "mcapi.transaction.operation"
 * )
 */
class Delete extends \Drupal\mcapi\Plugin\TransactionActionBase {

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $elements = parent::buildConfigurationForm($form, $form_state);
    unset($elements['access']['erased']);
    //if the transaction no longer exists there's nothing to configure for the final step
    unset($elements['feedback']['redirect']['#states']);
    //because after a transaction is deleted, you can't very well go and visit it.
    $elements['feedback']['redirect']['#required'] = TRUE;
    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function execute($object = NULL) {
    $object->delete();
    if ($this->configuration['message']) {
      drupal_set_message($this->configuration['message']);
    }
  }

}
