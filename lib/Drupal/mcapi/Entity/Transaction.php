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
 * @EntityType(
 *   id = "mcapi_transaction",
 *   label = @Translation("Transaction"),
 *   module = "mcapi",
 *   controllers = {
 *     "storage" = "Drupal\mcapi\TransactionStorageController",
 *     "view_builder" = "Drupal\mcapi\TransactionViewBuilder",
 *     "access" = "Drupal\mcapi\TransactionAccessController",
 *     "form" = {
 *       "add" = "Drupal\mcapi\TransactionFormController",
 *       "edit" = "Drupal\mcapi\TransactionFormController",
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
 *     "canonical" = "/transaction/{mcapi_transaction}"
 *   }
 * )
 */
class Transaction extends ContentEntityBase implements TransactionInterface {
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

  /**
   * {@inheritdoc}
   */
  public function preSave(EntityStorageControllerInterface $storage_controller) {
    //TODO: Change this so that you only create new serial numbers on the parent transaction.
    if ($this->isNew() && !$this->serial->value) {
      $storage_controller->nextSerial($this);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function postSave(EntityStorageControllerInterface $storage_controller, $update = TRUE) {
    parent::postSave($storage_controller, $update);

    $storage_controller->saveWorths($this);
    $storage_controller->addIndex($this);
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
      'created' => REQUEST_TIME,
      'type' => 'default',
      'extra' => array(),
      'state' => TRANSACTION_STATE_FINISHED
    );

    if (empty($values['worth'])) {
      $values['worth'] = array();
      foreach (mcapi_get_available_currencies() as $currcode => $name) {
        $values['worths'][$currcode] = array(
          'currcode' => $currcode,
          'quantity' => NULL,
        );
      }
    }
    else {
      foreach ($values['worth'] as $currcode => $value) {
        $values['worths'] += array(
          'currcode' => $currcode,
          'quantity' => NULL,
        );
      }
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
