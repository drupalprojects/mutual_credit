<?php

/**
 * @file
 * Contains \Drupal\mcapi\TransactionStorageController.
 * this uses sql for speed rather than the Drupal DbAPI
 */

namespace Drupal\mcapi;

use Drupal\Core\Entity\FieldableDatabaseStorageController;
use Drupal\mcapi\Plugin\field\field_type\Worth;

class TransactionStorageController extends FieldableDatabaseStorageController implements TransactionStorageControllerInterface {

  /**
   * Overrides Drupal\Core\Entity\DatabaseStorageController::attachLoad().
   */
  function attachLoad(&$queried_entities, $load_revision = FALSE) {
    $result = $this->database->query('SELECT * FROM {mcapi_transactions_worths} WHERE xid IN (:xids)', array(':xids' => array_keys($queried_entities)));
    foreach ($result as $record) {
      $queried_entities[$record->xid]->worths[$record->currcode] = array(
        'currcode' => $record->currcode,
        'quantity' => $record->quantity,
      );
    }

    // Load all the children
    $result = $this->database->query('SELECT xid FROM {mcapi_transactions} WHERE parent IN (:parents)', array(':parents' => array_keys($queried_entities)));
    foreach ($result as $record) {
      $queried_entities[$record->xid]->children[$record->xid] = $record->xid;
    }

    parent::attachLoad($queried_entities, $load_revision);
  }
  /*
   * overrides of this delete function may choose to ignore the delete mode
   */
  public function delete(array $transaction) {
    $delete_state = \Drupal::config(mcapi.misc)->get('delete_mode');
  	switch($delete_state) {
  		case MCAPI_UNDO_STATE_REVERSE:
  			//add reverse transactions to the children and change the state.
  			foreach (array_merge(array($transaction), $transaction->children) as $transaction) {
  				$reversed = clone $transaction;
  				$reversed->payer = $transaction->payee;
  				$reversed->payee = $transaction->payer;
  				$reversed->type = 'reversal';
  				unset($reversed->created);
  				$reversed->description = t('Reversal of: @label', array('@label' => $entity['label callback']($transaction)));
  				$reverseds[] = $reversed;
  			}
  			$transaction->children = array_merge($transaction->children, $reversed);
        //running on..
  	  case MCAPI_UNDO_STATE_ERASE:
  			$old_state = $transaction->state;
  			$transaction->state = $delete_state;
  			try{
  	  		//notice we don't validate here
  				$transaction->save();
  				drupal_set_message('update hook needed in TransactionStorageController->delete()');
  			}
  			catch (Exception $e){
  				drupal_set_message(t('Failed to undo transaction: @message', array('@message' => $e->getMessage())));
  			}
  			break;
  		case MCAPI_UNDO_STATE_DELETE:
	      $this->database->delete('mcapi_transactions_worths')
	        ->condition('xid', $transaction->id())
	        ->execute();
	      //and the index table
	      $this->database->delete('mcapi_transactions_index')
	        ->condition('xid', $transaction->id())
	        ->execute();
  	}
	  module_invoke_all('transaction_undo', $transaction->serial->value);
	  //once even one transaction has been deleted, the undo_mode cannot be changed
	  \Drupal::config('mcapi.misc')->set('change_undo_mode', FALSE);
  }


  /**
   * {@inheritdoc}
   */
  public function saveWorths(TransactionInterface $transaction) {
    $this->database->delete('mcapi_transactions_worths')
      ->condition('xid', $transaction->id())
      ->execute();
drupal_set_message("failing to saveWorths");
    $query = $this->database->insert('mcapi_transactions_worths')
      ->fields(array('xid', 'currcode', 'quantity'));
    foreach ($transaction->worths[0] as $currcode => $worthitem) {
    	if (!$worthitem->quantity) continue;
      $query->values(array(
        'xid' => $transaction->id(),
        'currcode' => $currcode,
        'quantity' => $worthitem->quantity,
      ));
    }
    $query->execute();

    //fire hooks -
    //transaction_update($op, $transaction, $values);
  }
  /*
   *  write 2 rows to the transaction index table
   */
  public function addIndex(TransactionInterface $transaction) {
    $this->database->delete('mcapi_transactions_index')
      ->condition('xid', $transaction->id())
      ->execute();
    //we only index transactions with positive state values
    if ($transaction->state->value < 1) return;
    drupal_set_message('Failing to retrieve worthItems in TransactionStorageController -> addIndex');return;
    foreach ($transaction->worths[0] as $currcode => $worthitem) {
      $query = $this->database->insert('mcapi_transactions_index')
        ->fields(array('xid', 'uid1', 'uid2', 'currcode', 'volume', 'incoming', 'outgoing', 'diff', 'type', 'created'));
      $query->values(array(
        'xid' => $transaction->id(),
        'uid1' => $transaction->payer->value,
        'uid2' => $transaction->payee->value,
        'currcode' => $currcode,
        'volume' => $worthitem->quantity,
        'incoming' => 0,
        'outgoing' => $worthitem->quantity,
        'diff' => -$worthitem->quantity,
        'type' => $transaction->type->value,
      	'created' => $transaction->created->value
      ),
      array(
        'xid' => $transaction->id(),
        'uid1' => $transaction->payee->value,
        'uid2' => $transaction->payer->value,
        'currcode' => $currcode,
        'volume' => $worthitem->quantity,
        'incoming' => $worthitem->quantity,
        'outgoing' => 0,
        'diff' => $worthitem->quantity,
        'type' => $transaction->type->value,
      	'created' => $transaction->created->value
      ));
      $query->execute();
    }
  }

  //this probably wants to be a batch
  public function indexRebuild() {
    db_truncate('mcapi_transactions_index')->execute();
    db_query("INSERT INTO {mcapi_transactions_index} (SELECT
        t.xid,
        t.payer AS uid1,
        t.payee AS uid2,
        t.state,
        t.type,
        t.created,
        w.currcode,
        0 AS income,
        w.quantity AS outgoing,
        - w.quantity AS diff,
        w.quantity AS volume
      FROM {mcapi_transactions} t
      RIGHT JOIN {mcapi_transactions_worths} w ON t.xid = w.xid
      WHERE state > 0) "
    );
    db_query("INSERT INTO {mcapi_transactions_index} (SELECT
        t.xid,
        t.payer AS uid1,
        t.payee AS uid2,
        t.state,
        t.type,
        t.created,
        w.currcode,
        w.quantity AS income,
        0 AS outgoing,
        w.quantity AS diff,
        w.quantity AS volume
      FROM {mcapi_transactions} t
      RIGHT JOIN {mcapi_transactions_worths} w ON t.xid = w.xid
      WHERE state > 0) "
    );
  }
  /*
   * return 1 if the table is integral
   */
  public function indexCheck() {
    if (db_query("SELECT SUM (diff) FROM {mcapi_transactions_index}")->fetchField() +0 == 0) {
      $volume_index = db_query("SELECT sum(income) FROM {mcapi_transactions_index}")->fetchField();
      $volume = db_query("SELECT sum(quantity) FROM {mcapi_transactions} t LEFT JOIN {mcapi_transactions_worths} w ON t.xid = w.xid AND t.state > 0")->fetchField();
      if ($volume_index == $volume) return TRUE;
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function nextSerial(TransactionInterface $transaction) {
    //TODO: I think this needs some form of locking so that we can't get duplicate transactions.
    $transaction->serial->value = $this->database->query("SELECT MAX(serial) FROM {mcapi_transactions}")->fetchField() + 1;
  }
}
