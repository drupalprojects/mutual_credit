<?php

/**
 * @file
 * Contains \Drupal\mcapi\Entity\Transaction.
 */

namespace Drupal\mcapi\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityStorageControllerInterface;
use Drupal\Core\Template\Attribute;
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
 *       "operation" = "Drupal\mcapi\Form\OperationForm",
 *       "admin" = "Drupal\mcapi\Form\TransactionForm",
 *       "delete" = "Drupal\mcapi\Form\TransactionDeleteConfirm"
 *     },
 *   },
 *   admin_permission = "configure mcapi",
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
 *     "admin-form" = "mcapi.admin.transactions"
 *   }
 * )
 */
class Transaction extends ContentEntityBase implements TransactionInterface {

  public $exceptions = array();
  public $child_candidates = array();

  /**
   * {@inheritdoc}
   */
  public function id() {
    return $this->get('xid')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function label($langcode = NULL) {
    return t("Transaction #@serial", array('@serial' => $this->serial->value));
  }

  /**
   * {@inheritdoc}
   */
  public function uri($rel = 'canonical') {
    $renderable = parent::uri($rel);
    $renderable['path'] = 'transaction/'. $this->get('serial')->value;
    return $renderable;
  }

  /**
   * @see \Drupal\mcapi\TransactionInterface::links()
   */
  function links($mode = 'page', $view = FALSE) {
    $renderable = array();
    if ($serial = $this->serial->value) {
      foreach (show_transaction_operations($view) as $op => $plugin) {
        if ($this->access($op)) {
          $renderable['#links'][$op] = array(
            'title' => $plugin->label,
            'route_name' => $op == 'view' ? 'mcapi.transaction_view' : 'mcapi.transaction.op',
            'route_parameters' => array(
              'mcapi_transaction' => $this->serial->value,
              'op' => $op
            ),
          );
          if ($mode == 'modal') {
            $renderable['#links'][$op]['attributes']['data-accepts'] = 'application/vnd.drupal-modal';
            $renderable['#links'][$op]['attributes']['class'][] = 'use-ajax';
          }
          elseif($mode == 'ajax') {
            //I think we need a new router path for this...
            $renderable['#attached']['js'][] = 'core/misc/ajax.js';
            //$renderable['#links'][$op]['attributes']['class'][] = 'use-ajax';
          }
          else{
            $renderable['#links'][$op]['query'] = drupal_get_destination();
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
   * @see \Drupal\mcapi\TransactionInterface::validate()
   */
  public function validate() {


    \Drupal::moduleHandler()->alter('mcapi_transaction_pre_validate', $this);

    $this->exceptions = array();
    list($payer, $payee) = mcapi_get_endpoints($this, TRUE);
    //check that the payer and payee are not the same wallet
    if ($payer->id() == $payee->id()) {
      $this->exceptions[] = new McapiTransactionException(
          '',
          t('A wallet cannot pay itself.')
      );
    }

    //determine which exchange to use, based on the payer, the payee and the currency(s).
    $exchange = $this->set('exchange', $this->derive_exchange());

    /*
     * @TODO GORDON I can't spend any more time trying to figure out the worths object.
     * foreach ($this->get('worths') gives us an array of worths it makes no sense.
     * I don't want to interrogate each $worth object, I just want an array of values keyed by currcode.
     * what about $this->get('worths') ?
     */
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
    //check that the uuid doesn't already exist. i.e. that we are not resubmitting the same transactions
    $properties = array('uuid' => $this->uuid->value);
    if (entity_load_multiple_by_properties('mcapi_transaction', $properties)) {
      $this->exceptions[] = new McapiTransactionException('',
        t('Transaction has already been inserted: @uuid', array('@uuid' => $this->uuid->value))
      );
    }
    //there might be a use-case when things are done out of order, so this is intended as a temp flag
    $this->validated = TRUE;

    //While we're validating the parent ONLY, pass the transaction cluster around
    if ($this->get('parent')->value == 0) {

      //prevent the main transaction being altered as we pass it round
      //the main transaction was already altered in the preValidate hook.
      $clone = clone($this);
      //we don't use $this->children at all - damned entity reference field isn't needed at this stage
      $this->child_candidates = array();//ONLY parent transactions have this property
      //previous the children's parent was set to temp, but is that necessary?
      $this->child_candidates = \Drupal::moduleHandler()->invokeAll('mcapi_transaction_children', array($clone));
      \Drupal::moduleHandler()->alter('mcapi_transaction_children', $this->child_candidates, $clone);

      $messages = array();
      //validate the child candidates
      foreach ($this->child_candidates as $child) {
        $child->set('parent', -1);//temp value. prevents recursion here but for some reason isn't there in presave
        $child->validate();
      }
      $cluster = mcapi_transaction_flatten($this);
      \Drupal::moduleHandler()->invokeAll('mcapi_transaction_validate', $cluster);

      //process the errors in the children.
      $child_errors = \Drupal::config('mcapi.misc')->get('child_errors');
      foreach ($this->child_candidates as $key => $child) {
        if ($child->exceptions) {
          foreach ($child->exceptions as $exception) {
            $messages[] = $exception->getmessage();
          }
          $replacements = array(
            '!messages' => implode(' ', $messages),
            '!dump' => print_r($this, 1)
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
   * @see \Drupal\mcapi\TransactionInterface::preSave($storage_controller)
   */
  public function preSave(EntityStorageControllerInterface $storage_controller) {
    if ($this->isNew()) {
      //TODO: Change this so that you only create new serial numbers on the parent transaction.
      if (!$this->get('serial')->value) {
        $storage_controller->nextSerial($this);//this serial number is not final, or at least not nocked
        //TODO Gordon when does the serial number become final?
        //this is the only time we actually call nextSerial
        //That's when we need to propagate it to the children.
        //Why do we need a serial number which isn't final
      }
      if (empty($this->get('created')->value)) {
        $this->get('created')->value = REQUEST_TIME;
      }
      if (empty($this->get('creator')->value)) {
        $this->set('creator', \Drupal::currentUser()->id());
      }
      if (property_exists($this, 'child_candidates')) {//that means $this is the original transaction
        foreach ($this->child_candidates as $transaction) {
          $transaction->set('serial', $this->get('serial')->value);
          $transaction->set('parent', $this->get('xid')->value);
          $transaction->set('exchange', $this->get('exchange')->value);
          $transaction->save();
          //$this->children[] = $transaction;
        }
      }
    }

  }


  /**
   * @see \Drupal\mcapi\TransactionInterface::postSave()
   */
  public function postSave(EntityStorageControllerInterface $storage_controller, $update = TRUE) {
    parent::postSave($storage_controller, $update);
    $storage_controller->saveWorths($this);
    $storage_controller->addIndex($this);
  }

  /**
   * @see \Drupal\mcapi\TransactionInterface::preCreate()
   */
  public static function preCreate(EntityStorageControllerInterface $storage_controller, array &$values) {
    parent::preCreate($storage_controller, $values);
    //@todo this is where we ensure that $values['currcode'] and $values['value'] are in a proper worth field
    $values += array(
      'description' => '',
      'parent' => 0,
      'payer' => NULL,
      'payee' => NULL,
      'creator' => \Drupal::currentUser()->id(),
      'type' => 'default',
      'extra' => array(),
      'worths' => array(),
      'exchange' => 0
    );
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
      ->setSettings(array('target_type' => 'mcapi_transaction', 'default_value' => 0));
    $properties['worths'] = FieldDefinition::create('worths')
      ->setLabel('Worth')
      ->setDescription('Value of this transaction')
      ->setRequired(TRUE);
    $properties['payer'] = FieldDefinition::create('entity_reference')
      ->setLabel('Payer')
      ->setDescription('Wallet id of the payer')
      ->setSettings(array('target_type' => 'mcapi_wallet'))
      ->setRequired(TRUE);
    $properties['payee'] = FieldDefinition::create('entity_reference')
      ->setLabel('Payee')
      ->setDescription('Wallet id of the payee')
      ->setSettings(array('target_type' => 'mcapi_wallet'))
      ->setRequired(TRUE);
    //quantity is done, perhaps controversially, but the field API
    $properties['type'] = FieldDefinition::create('string')
      ->setLabel('Type')
      ->setDescription('The type of transaction, types are provided by modules')
      ->setPropertyConstraints('value', array('Length' => array('max' => 32)))
      ->setSettings(array('default_value' => 'default'))
      ->setRequired(TRUE);
    $properties['state'] = FieldDefinition::create('integer')
      ->setLabel('State')
      ->setDescription('Completed, pending, disputed, etc')
      ->setSettings(array('default_value' => 1));
    $properties['creator'] = FieldDefinition::create('entity_reference')
      ->setLabel('Creator')
      ->setDescription('the user id of the creator')
      ->setSettings(array('target_type' => 'user'))
      ->setRequired(TRUE);
    $properties['exchange'] = FieldDefinition::create('entity_reference')
      ->setLabel('Exchange')
      ->setDescription('The exchange in which this transaction happened')
      ->setSettings(array('target_type' => 'mcapi_exchange'));
    // @todo Convert to a "timestamp" field in https://drupal.org/node/2145103.
    $properties['created'] = FieldDefinition::create('integer')
      ->setLabel('Created')
      ->setDescription('The time that the transaction was created.')
      ->setReadOnly(TRUE);
    $properties['children'] = FieldDefinition::create('entity_reference')
      ->setLabel('Children')
      ->setDescription('List of all child transactions.')
      ->setSettings(array('target_type' => 'mcapi_transaction'));

    return $properties;
  }

  /**
   * work out an exchange that this transaction can be in, based on the participants and the currency
   * @return integer
   *   the exchange entity
   */
  private function derive_exchange() {
    $exchanges = array_intersect_key(
      referenced_exchanges($this->get('payer')->entity->getOwner()),
      referenced_exchanges($this->get('payee')->entity->getOwner())
    );

    if (empty($exchanges)) {
      //we can't choose a fieldname here because we know the 1stparty module doesn't display payer and payee fields
      throw new McapiTransactionException (
        '',
        t(
          'Wallets @num1 & @num2 do not have an exchange in common.',
          array('@num1' => $this->get('payer')->value, '@num2' => $this->get('payee')->value)
        )
      );
    }

    foreach($this->get('worths')->getValue() as $delta => $worth) {
      //Should be able to just pull them out of the worths field as this array
      $worth = current($worth);
      $transaction_worths[$worth['currcode']] = $worth['value'];
    }
    $allowed_currcodes = exchange_currencies($exchanges);
    foreach ($exchanges as $id => $exchange) {
      $access = TRUE;
      $allowed_currencies = exchange_currencies(array($exchange));
      $leftover = array_diff_key($transaction_worths, $allowed_currencies);
      if ($leftover) continue; //try another exchange
      return $exchange;//the first exchange which has all the needed currencies available
    }
    throw new McapiTransactionException ('', t(
      "Currency '!name' is not available to both wallets",
      array('!name' => entity_load('mcapi_currency', key($leftover))->label())
    ));
  }

}

//@todo Gordon I can't believe this is the simplest way
//to retrieve a value from an entity single reference field.
//hideous!
function mcapi_get_endpoints($transaction) {
  $payer = $transaction->get('payer')->getValue(true);
  $payee = $transaction->get('payee')->getValue(true);
  return array($payer[0]['entity'], $payee[0]['entity']);

}