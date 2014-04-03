<?php

/**
 * @file
 * Contains \Drupal\mcapi\Entity\Transaction.
 */

namespace Drupal\mcapi\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityStorageControllerInterface;
use Drupal\Core\Session\AccountInterface;
//use Drupal\Core\Field\FieldDefinition;
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
 *       "delete" = "Drupal\mcapi\Form\TransactionDeleteConfirm",
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
  public $child = array();

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
   * @see \Drupal\mcapi\TransactionInterface::validate()
   */
  public function validate() {
    //currently this isn't being used
    \Drupal::moduleHandler()->alter('mcapi_transaction_pre_validate', $this);
    $this->exceptions = array();
    list($payer, $payee) = $this->endpoints();
    //check that the payer and payee are not the same wallet
    if ($payer->id() == $payee->id()) {
      $this->exceptions[] = new McapiTransactionException(
          '',
          t('A wallet cannot pay itself.')
      );
    }
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
    //on the parent transaction only, validate it and then pass it round to get child candidates
    if ($this->get('parent')->value == 0) {

      //prevent the main transaction being altered as we pass it round
      //the main transaction was already altered in the preValidate hook.
      $clone = clone($this);
      $this->children = array();//ONLY parent transactions have this property
      //previous the children's parent was set to temp, but is that necessary?
      $this->children = \Drupal::moduleHandler()->invokeAll('mcapi_transaction_children', array($clone));
      \Drupal::moduleHandler()->alter('mcapi_transaction_children', $this->children, $clone);
      //how the children have been created but before they are validated, we to the intertrading split

      //We determine the exchange by finding one in common and checking it supports the required currency.
      //<<groan>> there has to be a better way to get the entities out of the entity_reference field
      $payer_exchanges = referenced_exchanges($this->get('payer')->entity->getOwner());
      $payee_exchanges = referenced_exchanges($this->get('payee')->entity->getOwner());

      //reformat the worths so we can work with them
      foreach($this->get('worths')->getValue() as $delta => $worth) {
        //Should be able to just pull them out of the worths field as this array
        $worth = current($worth);
        $transaction_worths[$worth['currcode']] = $worth['value'];
      }
      $allowed_currcodes = exchange_currencies($exchanges);
      foreach (array_intersect_key($payer_exchanges, $payee_exchanges) as $id => $exchange) {
        $access = TRUE;
        $allowed_currencies = exchange_currencies(array($exchange));
        $leftover = array_diff_key($transaction_worths, $allowed_currencies);
        if ($leftover) continue; //try another exchange
        $this->set('exchange', $exchange);
        break;
      }
      //with no exchange/currency combination available, we now try intertrading
      if (!$this->get('exchange')->value) {
        if ($child = $this->intertrade_child($payer_exchanges, $payee_exchanges)) {
          //$this->exchange has now been set
          $this->children[] = $child;
        }
      }
      $messages = array();
      //with no exchange/currency combination available, we now try intertrading
      if (!$this->get('exchange')->value) {
        $this->set('exchange', reset(referenced_exchanges()));
        //put this transaction in the current users' first exchange, just to make it valid
        $this->exceptions[] = new McapiTransactionException ('exchange', t('The payer and payee have no exchanges in common'));
      }

      //validate the child candidates
      foreach ($this->children as $child) {
        $child->set('parent', -1);//temp value. prevents recursion here but for some reason isn't there in presave
        $child->validate();
      }
      $cluster = mcapi_transaction_flatten($this);
      \Drupal::moduleHandler()->invokeAll('mcapi_transaction_validate', array($cluster));

      //process the errors in the children.
      $child_errors = \Drupal::config('mcapi.misc')->get('child_errors');
      foreach ($this->children as $key => $child) {
        if ($child->exceptions) {
          foreach ($child->exceptions as $exception) {
            $messages[] = $exception->getmessage();
          }
          $replacements = array(
            '!messages' => implode(' ', $messages),
            //'!dump' => print_r($this, TRUE)//TODO for some reason print_r is printing not returning
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
          	  user_load(1)->mail,
          	  t('Child transaction error on !site', array('!site' => \Drupal::config('system.site')->get('name'))),
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
        //Why do we need a serial number which isn't final?
      }
      if (empty($this->get('created')->value)) {
        $this->get('created')->value = REQUEST_TIME;
      }
      if (empty($this->get('creator')->value)) {
        $this->set('creator', \Drupal::currentUser()->id());
      }
    }
  }

  /**
   * @see \Drupal\mcapi\TransactionInterface::postSave()
   * save the child transactions, which refer to the saved parent
   */
  public function postSave(EntityStorageControllerInterface $storage_controller, $update = TRUE) {
    foreach ($this->children as $transaction) {
      $transaction->set('serial', $this->get('serial')->value);
      $transaction->set('parent', $this->get('xid')->value);
      $transaction->save();
    }


    parent::postSave($storage_controller, $update);
    $storage_controller->saveWorths($this);
    $storage_controller->addIndex($this);

    //TODO clear the entity cache of all wallets involved in this transaction and its children
    //because the wallet entity cache contains the balance limits and the summary stats
    drupal_set_message ('todo: clear wallet cache');
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
    $properties['xid'] = array(
    	'type' => 'integer_field',
      'label' => t('Transaction ID'),
      'description' => t('the unique transaction ID'),
      'readonly' => TRUE
    );
    $properties['uuid'] = array(
      'label' => t('UUID'),
      'description' => t('The transaction UUID.'),
      'type' => 'uuid_field',
      'read-only' => TRUE,
      'required' => TRUE
    );
    $properties['description'] = array(
      'label' => t('Description'),
      'description' => t('A one line description of what was exchanged.'),
      'type' => 'string_field',
      'required' => FALSE,
      'settings' => array(
        'default_value' => '',
      ),
      'property_constraints' => array(
        'value' => array('Length' => array('max' => 128)),
      ),
      'translatable' => FALSE,
    );
    $properties['serial'] = array(
      'label' => t('Serial number'),
      'description' => t('Grouping of related transactions.'),
      'type' => 'integer_field',
      'read-only' => TRUE,
    );
    $properties['parent'] = array(
      'label' => t('Parent'),
      'description' => t('Parent transaction that created this transaction.'),
      'type' => 'entity_reference_field',
      'settings' => array(
        'target_type' => 'mcapi_transaction',
        'default_value' => 0,
      ),
    );
    $properties['worths'] = array(
    	'type' => 'worths_field',
      'label' => t('Worth'),
      'description' => t('Value of this transaction.'),
      'required' => TRUE
    );
    $properties['payer'] = array(
      'label' => t('Payer'),
      'description' => t('Wallet id of the payer.'),
      'type' => 'entity_reference_field',
      'settings' => array(
        'target_type' => 'mcapi_wallet',
        'default_value' => 0,
      ),
      'required' => TRUE
    );
    $properties['payee'] = array(
      'label' => t('Payee'),
      'description' => t('Wallet id of the payee.'),
      'type' => 'entity_reference_field',
      'settings' => array(
        'target_type' => 'mcapi_wallet',
        'default_value' => 0,
      ),
      'required' => TRUE
    );
    $properties['type'] = array(
      'label' => t('Type'),
      'description' => t('The type of transaction, types are provided by modules.'),
      'type' => 'string_field',
      'read-only' => TRUE,
      'required' => TRUE,
      'property_constraints' => array(
        'value' => array('Length' => array('max' => 32)),
      ),
      'settings' => array(
        'default_value' => 'default'
      )
    );
    $properties['state'] = array(
      'label' => t('State'),
      'description' => t('Completed, pending, disputed, etc.'),
      'type' => 'integer_field',
      'read-only' => FALSE,
      'settings' => array(
        'default_value' => 1
      )
    );
    $properties['creator'] = array(
      'label' => t('Creator'),
      'description' => t('The user who created the transaction'),
      'type' => 'entity_reference_field',
      'settings' => array(
        'target_type' => 'user',
        'default_value' => 0,
      ),
      'required' => TRUE,
      'read-only' => TRUE,
    );
    $properties['exchange'] = array(
      'label' => t('Exchange'),
      'description' => t('The exchange in which this transaction happened.'),
      'type' => 'entity_reference_field',
      'settings' => array(
        'target_type' => 'mcapi_exchange',
        'default_value' => 0,
      ),
      'required' => TRUE,
      'read-only' => TRUE,
    );
    $properties['created'] = array(
      'label' => t('Created'),
      'description' => t('The time that the transaction was created.'),
      'type' => 'integer_field',
    );
    return $properties;
  }

  //@todo Gordon I can't believe this is the simplest way
  //to retrieve a value from an entity single reference field.
  //hideous!
  //get the fully loaded wallets
  function endpoints() {
    $payer = $this->get('payer')->getValue(true);
    $payee = $this->get('payee')->getValue(true);
    return array($payer[0]['entity'], $payee[0]['entity']);
  }

  /*
   * Split one transaction into 2, going between exchanges and currencies
   * the payer intertrading transaction is the 'main' transaction while
   * the payee intertrading transaction is the child transaction
   *
   * in order to intertrade we need to test that...
   * each wallet owner is in an open exchange
   * the currency of the main transaction has a ticks value
   * which currency to pay the remote user in
   *
   * does this function belong in this object?
   */
  private function intertrade_child($payer_exchanges, $payee_exchanges) {
    //remove nonopen & nonactive exchanges
    foreach(array('payer_exchanges', 'payee_exchanges') as $exchanges) {
      foreach ($$exchanges as $key => $ex) {
        if ($ex->get('open')->value && $ex->get('active')->value) continue;
        unset($$exchanges[$key]);
      }
      if (empty($$exchanges)) return;
    }

    if (empty($payer_currs) || empty($payee_currs)) return;
    $payer_currs = exchange_currencies($payer_exchanges, TRUE);//if this doesn't contain all the currencies of this transaction, something is wrong
    $payee_currs = exchange_currencies($payee_exchanges, TRUE);

    //which of these valid currencies will the partner receive?
    $shared_currs = array_intersect_key($payer_currs, $payee_currs);//likely to be backed currencies, available in both exchanges

    $partner_curr = reset(array_diff_key($payee_currs, $payer_currs));
    //if there is more than one payee_curr, how do we decide what is the intertrading currency?
    //if more than one exchange is available with the required currencies, how do we decide which?
    //generally, pick the first using reset()
    $this->set('exchange', reset($payer_exchanges));

    $child = clone($this);
    $child->set('uuid', \Drupal::service('uuid')->generate());
    $child->set('exchange', reset($payee_exchanges));
    //its not obvious how to update an entity_reference field and reload the referenced entity, but this works for now
    unset($this->payee);
    $this->payee->setValue(reset($payer_exchanges)->intertrading_wallet(), TRUE);
    unset($child->payer);
    $child->payer->setValue(reset($payee_exchanges)->intertrading_wallet(), TRUE);
    unset($child->worths);
    $payee_currency = reset($payee_currs);
    $payer_currency = reset($payer_currs);
    foreach ($this->worths as $worth) {
      $val = current($worth->getValue());
      if (isset($shared_currs[$val['currcode']])) {
        //simply copy the worth value
        $child->worths[] = $worth;
      }
      else {//this assumes that only ONE of the source worths will be converted to target currency
        $new_worth = current($worth->getValue());
        $child->worths[] = array(
          $partner_curr->id() => array(
        	  'currcode' => $partner_curr->id(),
            'value' => intval($new_worth['value'] * $payer_currency->get('ticks') / $payee_currency->get('ticks'))//don't worry about rounding
          )
        );
      }
    }
    return $child;
  }

  function worth_convert($worth, $dest_currency) {
    return $worth;
  }
}
