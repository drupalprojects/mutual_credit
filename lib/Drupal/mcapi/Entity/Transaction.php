<?php

/**
 * @file
 * Contains \Drupal\mcapi\Entity\Transaction.
 */

namespace Drupal\mcapi\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityStorageControllerInterface;
use Drupal\Core\Template\Attribute;
use Drupal\Core\Language\Language;
use Drupal\Core\Session\AccountInterface;
use Drupal\mcapi\TransactionInterface;
use Drupal\mcapi\McapiTransactionException;

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

  public $errors = array();
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

  /*
   * return a render array of 'operations' links for the current user on $this transaction
   * $mode can be 'page', 'ajax, or 'modal'
   * $view is whether or not to include the 'view' link
   */
  function links($mode = 'page', $view = FALSE) {
    $renderable = array();
    foreach (transaction_operations() as $op => $plugin) {
      if (!$view && $op == 'view') continue;
      if ($this->access($op)) {
        if ($op == 'view') {//maybe this isn't necessary at all
          $renderable['#links'][$op] = array(
            'title' => $plugin->label,
            'route_name' => 'mcapi.transaction_view',
            'route_parameters' => array(
              'mcapi_transaction' => $this->serial->value
            ),
          );
        }
        else {
          $renderable['#links'][$op] = array(
            'title' => $plugin->label,
            'route_name' => 'mcapi.transaction.op',
            'route_parameters' => array(
              'mcapi_transaction' => $this->serial->value,
              'op' => $op
            ),
          );
        }
        if ($mode == 'modal') {
          $renderable['#links'][$op]['attributes']['data-accepts'] = 'application/vnd.drupal-modal';
          $renderable['#links'][$op]['attributes']['class'][] = 'use-ajax';
        }
        elseif($mode == 'ajax') {
          //I think we need a new router path for this...
          $renderable['#attached']['js'][] = 'core/misc/ajax.js';
          //$renderable['#links'][$op]['attributes']['class'][] = 'use-ajax';
        }
      }
    }
    if (array_key_exists('#links', $renderable)) {
      $renderable += array(
        '#theme' => 'links',
        //'#heading' => t('Operations'),
        '#attached' => array(
          'css' => array(drupal_get_path('module', 'mcapi') .'/mcapi.css')
        ),
        //Attribute class not found
        '#attributes' => new Attribute(array('class' => array('transaction-operations'))),
      );
    }
    return $renderable;
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
   */
  public function validate() {
    parent::validate();
    $this->exceptions = array();
    //check that each trader has permission to use all the currencies
    foreach (array($this->payer->entity, $this->payee->entity) as $account) {
      foreach ($this->worths[0] as $worth) {
        if (!$worth->currency->access('membership', $account)) {
          $this->exceptions[] = t('!user cannot use !currency', array('!user' => $account->getUsername(), '!currency' => $worth->currency->name));
        }
      }
    }
    if ($this->payer->value == $this->payee->value) {
      $transaction->exceptions[] = t('A wallet cannot pay itself.');
    }
    foreach ($this->worths[0] as $worth) {
      if ($worth->value <= 0) {
        echo get_class($worth->currency);
        $this->exceptions[] = new McapiTransactionWorthException($worth->currency, t('A transaction must be worth more than 0'));
      }
    }
    //it appears that $this->entity is the object created in the TransactionForm->preCreate, unmodified by the form submission
    //the entity is only rebuilt when submit handler runs
    //so what are we supposed to be validating?
    echo $this->type->value;
    //check that the state and type are congruent
    $types = mcapi_get_types(FALSE);
    if (array_key_exists($this->type->value, $types)) {
      if ($this->state->value != $types[$this->type->value]['start state']) {
        $this->exceptions[] = new McapiTransactionException(
          'state',
          t(
            'Wrong initial state for @type transaction: @state',
            array('@type' => $this->type->value, '@state' => $this->state->value)
          )
        );
      }
    }
    else {
      $this->exceptions[] = new McapiTransactionException(
        'type',
        t('Invalid transaction type: @type', array('@type' => $this->type->value))
      );
    }


    //child transactions which fail validation do so quietly, leaving a message in the log.
    //TODO this isn't working. $this->children is an entity reference object.
    //so how to we get the actual child transactions?
    foreach ($this->children as $key => $child) {
      //$child->validate();
    }

    if ($this->parent->value == 0) {
      //pass the transaction and its children around to other modules.
      //flatten in first to make it a bit easier to handle
      module_invoke_all('mcapi_transaction_validate', mcapi_transaction_flatten($this));
    }

    if (!empty($this->exceptions)) {
      //errors in the top level transaction mean that the saving can't go ahead and validation fails
      //TODO can I throw an array of exceptions?
      //do I need to throw them at all if the form validation is checking for $this->exceptions ?
      //throw $this->exceptions;
      //errors in the other transactions should show up as warnings on the 'are you sure' page.
    }
  }

  /**
   * {@inheritdoc}
   */
  public function postSave(EntityStorageControllerInterface $storage_controller, $update = TRUE) {
    parent::postSave($storage_controller, $update);
    $storage_controller->saveWorths($this);
    echo 'adding lines to index table';
    $storage_controller->addIndex($this);
    //save the children if there are any
    foreach ($this->children as $transaction) {
      $transaction->serial->value = $this->serial->value;
      $transaction->parent->value = $this->xid->value;
    }
  }

  /**
   * {@inheritdoc}
   * the only way to pass values into this is with the router system, but how?
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
      'worth' => array(),
    );
    $types = mcapi_get_types(FALSE);
    $values['state'] = $types[$values['type']]['start state'];

    if (!empty($values['worth'])) {
      foreach ($values['worth'] as $currcode => $value) {
        $values['worths'][$currcode] += array(
          'currcode' => $currcode,
          'value' => NULL,
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
        'default_value' => 1,
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
