<?php

/**
 * @file
 * Contains \Drupal\mcapi\TransactionStorageController.
 */

namespace Drupal\mcapi;

use Drupal\Core\Entity\FieldableDatabaseStorageController;

class TransactionStorageController extends FieldableDatabaseStorageController implements TransactionStorageControllerInterface {

  /**
   * Overrides Drupal\Core\Entity\DatabaseStorageController::attachLoad().
   */
  function attachLoad(&$queried_entities, $load_revision = FALSE) {
    $result = $this->database->query('SELECT * FROM {mcapi_transactions_worth} WHERE xid IN (:xids)', array(':xids' => array_keys($queried_entities)));
    foreach ($result as $record) {
      $queried_entities[$record->xid]->worth[$record->currcode] = array(
        'currcode' => $record->currcode,
        'quantity' => $record->quantity,
      );
    }

    parent::attachLoad($queried_entities, $load_revision);
  }

  /**
   * {@inheritdoc}
   */
  public function saveWorths(TransactionInterface $transaction) {
    $this->database->delete('mcapi_transactions_worth')
      ->condition('xid', $transaction->id())
      ->execute();

    $query = $this->database->insert('mcapi_transactions_worth')->fields(array('xid', 'currcode', 'quantity'));
    foreach ($transaction->worth[0] as $currcode => $currency) {
      $query->values(array(
        'xid' => $transaction->id(),
        'currcode' => $currcode,
        'quantity' => $currency->quantity,
      ));
    }
    $query->execute();
  }
}
