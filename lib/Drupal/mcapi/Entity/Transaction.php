<?php

/**
 * @file
 * Contains \Drupal\mcapi\Entity\Transaction.
 */

namespace Drupal\mcapi\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityStorageControllerInterface;
use Drupal\Core\Language\Language;
use Drupal\Core\Session\AccountInterface;
use Drupal\mcapi\TransactionInterface;

/**
 * Defines the Transaction entity.
 *
 * @EntityType(
 *   id = "mcapi_transaction",
 *   label = @Translation("Transaction"),
 *   module = "mcapi",
 *   controllers = {
 *     "storage" = "Drupal\mcapi\TransactionStorageController",
 *     "view_builder" = "Drupal\mcapi\TransactionViewBuilder",
 *     "access" = "Drupal\mcapi\TransactionAccessController",
 *     "form" = {
 *       "admin" = "Drupal\mcapi\Form\TransactionForm",
 *       "delete" = "Drupal\mcapi\Form\TransactionDeleteConfirm"
 *     },
 *   },
 *   admin_permission = "manage all transactions",
 *   base_table = "mcapi_transactions",
 *   entity_keys = {
 *     "id" = "xid",
 *     "uuid" = "uuid"
 *   },
 *   fieldable = TRUE,
 *   translatable = FALSE,
 *   route_base_path = "admin/accounting",
 *   links = {
 *     "canonical" = "/transaction/{mcapi_transaction}",
 *     "admin-form" = "mcapi.admin"
 *   }
 * )
 */
class Transaction extends ContentEntityBase implements TransactionInterface {

  var $errors = array();
  /**
   * Implements Drupal\Core\Entity\EntityInterface::id().
   */
  public function id() {
    return $this->get('xid')->value;
  }

  public function label($langcode = NULL) {
    return t("Transaction #@serial", array('@serial' => $this->serial->value));
  }

  public function uri($rel = 'canonical') {
    $renderable = parent::uri($rel);
    $renderable['path'] = 'transaction/'. $this->get('serial')->value;
    return $renderable;
  }

  public function buildChildren() {
    foreach (module_invoke_all('transaction_children', $this) as $transaction) {
      $transaction->parent = 'temp';//TODO how do we know the parent?
      $this->children[] = $transaction;
    }
  }

  /**
   * preSave
   * passes the transaction around allowing modules
   * populates the children property
   * handles errors with child transactions
   */
  public function preSave(EntityStorageControllerInterface $storage_controller) {
    //TODO: Change this so that you only create new serial numbers on the parent transaction.
    if ($this->isNew() && !$this->serial->value) {
      $storage_controller->nextSerial($this);//this serial number is not final, or at least not nocked
    }
    if (empty($this->created->value)) {
    	$this->created->value = REQUEST_TIME;
    }
    if (empty($this->creator->value)) {
    	$this->creator->value = \Drupal::currentUser()->id();
    }
    //build children if they haven't been built already
    if ($this->isNew() && !$this->parent && empty($this->children)) {
      //note that children do not have a serial number or parent xid until the postSave
      $this->buildChildren();
    }
    foreach ($this->children as $key => $transaction) {
      if (!empty($transaction->errors)) {
        watchdog(
          'Community Accounting',
          'A child transaction "!description" failed validation and was not saved.',
          array('!transaction' => print_r($transaction, 1)),
          WATCHDOG_ERROR
        );
        drupal_set_message(
          t('A child transaction "!description" failed validation and was not saved.', array('!description' => $transaction->description->value)),
          'warning'
        );
      }
    }
  }

  /**
   * Validate a transaction, with its children
   * TODO put this in an interface
   * This should ALWAYS be run inside a try{}
    * Should there be a transaction Exception class?
   */
  public function validate() {
    parent::validate();//the TypedData validator is complaining about something
    $errors = array();
    //check that each trader has permission to use all the currencies
    foreach (array($this->payer->entity, $this->payee->entity) as $account) {
      foreach ($this->worths[0] as $worth) {
        if (!$worth->currency->access('membership', $account)) {
          $errors[] = t('!user cannot use !currency', array('!user' => $account->name->value, '!currency' => $worth->currency->name));
        }
      }
    }
    $cluster = mcapi_transaction_flatten($this);
    //child transactions which fail validation do so quietly, leaving a message in the log.
    foreach ($cluster as $transaction) {
      $transaction->errors = array();
      if ($transaction->payer->value == $transaction->payee->value) {
        $transaction->errors[] = t('A wallet cannot pay itself.');
      }
      foreach ($transaction->worths[0] as $worth) {
        if ($worth->value <= 0) {
          $transaction->errors[] = t('A transaction must be worth more than 0;');
        }
      }
    }

    module_invoke_all('mcapi_transaction_validate', $cluster);

    if (!empty($this->errors)) {
      //errors in the top level transaction mean that the saving can't go ahead and validation fails
      throw new Exception(implode(' ', $errors));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function postSave(EntityStorageControllerInterface $storage_controller, $update = TRUE) {
    parent::postSave($storage_controller, $update);
    $storage_controller->saveWorths($this);
    $storage_controller->addIndex($this);
    //save the children if there are any
    foreach ($this->children as $transaction) {
      $transaction->serial->value = $this->serial->value;
      $transaction->parent->value = $this->xid->value;
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function preCreate(EntityStorageControllerInterface $storage_controller, array &$values) {
    $values += array(
      'description' => '',
      'parent' => 0,
      'payer' => NULL,
      'payee' => NULL,
      'creator' => \Drupal::currentUser()->id(),
      'created' => '',
      'type' => 'default',
      'extra' => array(),
      'state' => TRANSACTION_STATE_FINISHED,
      'worth' => array(),
    );

    if (!empty($values['worth'])) {
      foreach ($values['worth'] as $currcode => $value) {
        $values['worths'][$currcode] += array(
          'currcode' => $currcode,
          'value' => NULL,
        );
      }
    }
    //ask the system if any children are needed
    if ($values['parent'] == 0) {

    }
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions($entity_type) {
    $properties['xid'] = array(
      'label' => t('Transaction ID'),
      'description' => 'the unique transaction ID',
      'type' => 'integer_field',
      'read-only' => TRUE,
    );
    $properties['uuid'] = array(
      'label' => t('UUID'),
      'description' => t('The transaction UUID.'),
      'type' => 'uuid_field',
      'read-only' => TRUE,
    );
    $properties['description'] = array(
      'label' => t('Description'),
      'description' => t('A one line description of what was exchanged.'),
      'type' => 'string_field',
      'property_constraints' => array(
        'value' => array('Length' => array('max' => 128)),
      ),
    );
    $properties['serial'] = array(
      'label' => t('Serial Number'),
      'description' => t('Grouping of related transactions'),
      'type' => 'integer_field',
      'read-only' => TRUE,
    );
    $properties['parent'] = array(
      'label' => t('Parent'),
      'description' => t('Parent transaction that created this transaction'),
      'type' => 'entity_reference_field',
      'settings' => array(
        'target_type' => 'mcapi_transaction',
      ),
    );
    $properties['worths'] = array(
      'label' => t('Worth'),
      'description' => t('Value of this transaction'),
      'type' => 'worths_field',
      'required' => TRUE,
      'list' => FALSE,
    );
    $properties['payer'] = array(
      'label' => t('Payer'),
      'description' => t('the user id of the payer'),
      'type' => 'entity_reference_field',
      'settings' => array(
        'target_type' => 'user',
      ),
      'required' => TRUE,
    );
    $properties['payee'] = array(
      'label' => t('Payee'),
      'description' => t('the user id of the payee'),
      'type' => 'entity_reference_field',
      'settings' => array(
        'target_type' => 'user',
      ),
      'required' => TRUE,
    );
    //quantity is done, perhaps controversially, but the field API
    $properties['type'] = array(
      'label' => t('Type'),
      'description' => t('The type of transaction, types are provided by modules'),
      'type' => 'string_field',
      'property_constraints' => array(
        'value' => array('Length' => array('max' => 32)),
      ),
    );
    $properties['state'] = array(
      'label' => t('State'),
      'description' => t('completed, pending, disputed, etc'),
      'type' => 'integer_field',
      'settings' => array(
        'default_value' => 0,
      )
    );
    $properties['creator'] = array(
      'label' => t('Creator'),
      'description' => t('the user id of the creator'),
      'type' => 'entity_reference_field',
      'settings' => array(
        'target_type' => 'user',
      ),
      'required' => TRUE,
    );
    $properties['created'] = array(
      'label' => t('Created'),
      'description' => t('The time that the transaction was created.'),
      'type' => 'integer_field',
    );
    $properties['children'] = array(
      'label' => t('Children'),
      'description' => t('List of all child transactions.'),
      'type' => 'entity_reference_field',
      'settings' => array(
        'target_type' => 'mcapi_transaction',
      ),
    );
    return $properties;
  }
}
