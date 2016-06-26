<?php

namespace Drupal\mcapi\Plugin\Action;

use Drupal\mcapi\Plugin\TransactionActionBase;
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
class Delete extends TransactionActionBase {

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $elements = parent::buildConfigurationForm($form, $form_state);
    unset($elements['access']['erased']);
    // If transaction no longer exists we can't redirect to its canonical page.
    unset($elements['feedback']['redirect']['#states']);
    // After a transaction is deleted, you can't very well go and visit it.
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
