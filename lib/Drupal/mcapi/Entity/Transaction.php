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
use Drupal\Core\Field\FieldDefinition;
use Drupal\mcapi\TransactionInterface;
use Drupal\mcapi\McapiTransactionException;
use Drupal\mcapi\Plugin\Field\McapiTransactionWorthException;

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

  public $exceptions = array();
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
    if ($serial = $this->serial->value) {
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
    if (!$this->isNew()) return;
    //TODO: Change this so that you only create new serial numbers on the parent transaction.
    if (!$this->serial->value) {
      $storage_controller->nextSerial($this);//this serial number is not final, or at least not nocked
    }
    if (empty($this->created->value)) {
    	$this->created->value = REQUEST_TIME;
    }
    if (empty($this->creator->value)) {
    	$this->creator->value = \Drupal::currentUser()->id();
    }
    //build children if they haven't been built already
    if (!$this->parent && empty($this->children)) {
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
   * Validate a transaction, with its children.
   * Adds exceptions to each transaction's exception array
   * @return array $messages
   *   a flat list of messages from all transactions in the cluster
   */
  public function validate() {
    $this->exceptions = array();
    //check that each trader has permission to use all the currencies
    foreach (array($this->payer->entity, $this->payee->entity) as $account) {
      foreach ($this->worths[0] as $currcode => $worth) {
        if (!$worth->currency->access('membership', $account)) {
          $this->exceptions[] = new McapiTransactionWorthException(
            $worth->currency,
            t('!user cannot use !currency', array('!user' => $account->getUsername(), '!currency' => $worth->currency->name))
          );
        }
      }
    }
    if ($this->payer->value == $this->payee->value) {
      $transaction->exceptions[] = t('A wallet cannot pay itself.');
    }
    foreach ($this->worths[0] as $worth) {
      if ($worth->value <= 0) {
        $this->exceptions[] = new McapiTransactionWorthException(
          $worth->currency,
          t('A transaction must be worth more than 0')
        );
      }
    }
    //check that the state and type are congruent
    $types = mcapi_get_types();
    if (array_key_exists($this->type->value, $types)) {
      if ($this->state->value != $types[$this->type->value]->start_state) {
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
    //check that the uuid doesn't already exist.
    $properties = array('uuid' => $this->uuid->value);
    if (entity_load_multiple_by_properties('mcapi_transaction', $properties)) {
      $this->exceptions[] = new McapiTransactionException('',
        t('Transaction has already been inserted: @uuid', array('@uuid' => $transaction->uuid->value))
      );
    }


    //While we're validating the parent ONLY, pass the transaction cluster around
    if ($this->parent->value == 0) {
      $messages = array();
      $children = $this->children[0];
      //mdump($children->getEntity());die();
/* how to iterate though an entity reference field?
      foreach ($children as $child) {
        echo $key; mdump($child);die('entity validating child');
        $child->validate();
      }
*/
      $cluster = mcapi_transaction_flatten($this);
      \Drupal::moduleHandler()->invokeAll('mcapi_transaction_validate', $cluster);
      $child_errors = \Drupal::config('mcapi.misc')->get('child_errors');
      foreach ($this->children as $key => $child) {
        if ($child->exceptions) {
          foreach ($child->exceptions as $exception) {
            $messages[] = $exception->getmessage();
          }
          $replacements = array(
            '!messages' => implode(' ', $messages),
            '!dump' => print_r($transaction, 1)
          );
          if ($child_errors['watchdog']){
          	watchdog(
          	  'mcapi_children',
          	  "transaction failed validation with the following messages: !messages<pre>\n!dump</pre>",
          	  $replacements,
          	  WATCHDOG_ERROR
            );
          }
          if ($child_errors['mail_user1']){
            //should this be done using the drupal mail system?
            //my life is too short for that rigmarole
          	mail(
          	  entity_load('user', 1)->mail,
          	  t('Child transaction error on !site', array('!site' => me\Drupal::config('system.site')->get('name'))),
          	  $replacements['!messages'] ."\n\n". $replacements['!dump']
            );
          }
        }
      }
      //pass the non-fatal messages back
      return $messages;
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
   * this is called from the FieldableDatabaseStorage
   */
  public static function preCreate(EntityStorageControllerInterface $storage_controller, array &$values) {
    echo 'preCreate values: ';print_r($values);
    //mtrace();
    $values += array(
      'description' => '',
      'parent' => 0,
      'payer' => NULL,
      'payee' => NULL,
      'creator' => \Drupal::currentUser()->id(),
      'created' => '',
      'type' => 'default',
      'extra' => array(),
      'worths' => array(),
    );
    $types = mcapi_get_types(FALSE);
    //$values['state'] = $types[$values['type']]->start_state;
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions($entity_type) {
    $properties['xid'] = FieldDefinition::create('integer')
      ->setLabel('Transaction ID')
      ->setDescription('the unique transaction ID')
      ->setReadOnly(TRUE);
    $properties['uuid'] = FieldDefinition::create('uuid')
      ->setLabel('UUID')
      ->setDescription('The transaction UUID.')
      ->setReadOnly(TRUE);
    $properties['description'] = FieldDefinition::create('string')
      ->setLabel('Description')
      ->setDescription('A one line description of what was exchanged.')
      ->setPropertyConstraints('value', array('Length' => array('max' => 128)));
    $properties['serial'] = FieldDefinition::create('integer')
      ->setLabel('Serial Number')
      ->setDescription('Grouping of related transactions')
      ->setReadOnly(TRUE);
    $properties['parent'] = FieldDefinition::create('entity_reference')
      ->setLabel('Parent')
      ->setDescription('Parent transaction that created this transaction')
      ->setSettings(array(
          'target_type' => 'mcapi_transaction',
        ));
    $properties['worths'] = FieldDefinition::create('worths')
      ->setLabel('Worth')
      ->setDescription('Value of this transaction')
      ->setRequired(TRUE);
    $properties['payer'] = FieldDefinition::create('entity_reference')
      ->setLabel('Payer')
      ->setDescription('the user id of the payer')
      ->setSettings(array(
          'target_type' => 'user',
        ))
      ->setRequired(TRUE);
    $properties['payee'] = FieldDefinition::create('entity_reference')
      ->setLabel('Payee')
      ->setDescription('the user id of the payee')
      ->setSettings(array(
          'target_type' => 'user',
        ))
      ->setRequired(TRUE);
    //quantity is done, perhaps controversially, but the field API
    $properties['type'] = FieldDefinition::create('string')
      ->setLabel('Type')
      ->setDescription('The type of transaction, types are provided by modules')
      ->setPropertyConstraints('value', array('Length' => array('max' => 32)))
      ->setRequired(TRUE);
    $properties['state'] = FieldDefinition::create('integer')
      ->setLabel('State')
      ->setDescription('completed, pending, disputed, etc')
      ->setSettings(array(
          'default_value' => 1,
        ));
    $properties['creator'] = FieldDefinition::create('entity_reference')
      ->setLabel('Creator')
      ->setDescription('the user id of the creator')
      ->setSettings(array(
          'target_type' => 'user',
        ))
      ->setRequired(TRUE);
    $properties['created'] = FieldDefinition::create('integer')
      ->setLabel('Created')
      ->setDescription('The time that the transaction was created.')
      ->setReadOnly(TRUE);
    $properties['children'] = FieldDefinition::create('entity_reference')
      ->setLabel('Children')
      ->setDescription('List of all child transactions.')
      ->setSettings(array(
          'target_type' => 'mcapi_transaction',
        ));

    return $properties;
  }
}
