<?php

/**
 * @file
 * Contains \Drupal\mcapi\Entity\Transaction.
 * @todo on all entity types sort out the linkTemplates https://drupal.org/node/2221879
 */

namespace Drupal\mcapi\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Field\FieldDefinition;
use Drupal\mcapi\TransactionInterface;
use Drupal\mcapi\McapiTransactionException;
use Drupal\mcapi\Plugin\Field\McapiTransactionWorthException;
use Drupal\Core\Entity\EntityTypeInterface;

/**
 * Defines the Transaction entity.
 *
 * @ContentEntityType(
 *   id = "mcapi_transaction",
 *   label = @Translation("Transaction"),
 *   controllers = {
 *     "storage" = "Drupal\mcapi\Storage\TransactionStorage",
 *     "view_builder" = "Drupal\mcapi\ViewBuilder\TransactionViewBuilder",
 *     "access" = "Drupal\mcapi\Access\TransactionAccessController",
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
 *   route_base_path = "admin/accounting/transactions",
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
   * @todo update this in line with https://drupal.org/node/2221879
   * actually can this be removed?
   */
  public function uri($rel = 'canonical') {
    debug('This function is still used!!');
    $renderable = parent::uri($rel);
    $renderable['path'] = 'transaction/'. $this->get('serial')->value;
    return $renderable;
  }

  public function url($rel = 'canonical', $options = array()) {
    return parent::url($rel);
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

      //moduleHandler was not passed to the ContentEntity constructor
      \Drupal::moduleHandler()->alter('mcapi_transaction_children', $this->children, $clone);
      //how the children have been created but before they are validated, we to the intertrading split

      //We determine the exchange by finding one in common and checking it supports the required currency.
      //<<groan>> there has to be a better way to get the entities out of the entity_reference field
      $payer_exchanges = referenced_exchanges($this->get('payer')->entity->getOwner());
      $payee_exchanges = referenced_exchanges($this->get('payee')->entity->getOwner());

      foreach (array_intersect_key($payer_exchanges, $payee_exchanges) as $id => $exchange) {
        $access = TRUE;
        $allowed_currencies = exchange_currencies(array($exchange));
        $leftover = array_diff_key($this->get('field_worth')->getValue(), $allowed_currencies);
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
        $exchanges = referenced_exchanges();
        $this->set('exchange', reset($exchanges));
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
  public function preSave(EntityStorageInterface $storage_controller) {
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
  public function postSave(EntityStorageInterface $storage_controller, $update = TRUE) {
    foreach ($this->children as $transaction) {
      $transaction->set('serial', $this->get('serial')->value);
      $transaction->set('parent', $this->get('xid')->value);
      $transaction->save();
    }

    parent::postSave($storage_controller, $update);
//    $storage_controller->saveWorths($this);
    $storage_controller->addIndex($this);

    //TODO clear the entity cache of all wallets involved in this transaction and its children
    //because the wallet entity cache contains the balance limits and the summary stats
    drupal_set_message ('todo: clear wallet cache');
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    //note that the worth field is field API because we can't define multiple cardinality fields in this entity.
    $fields['xid'] = FieldDefinition::create('integer')
      ->setLabel(t('Transaction ID'))
      ->setDescription(t('The unique transaction ID.'))
      ->setReadOnly(TRUE)
      ->setSetting('unsigned', TRUE);

    $fields['uuid'] = FieldDefinition::create('uuid')
      ->setLabel(t('UUID'))
      ->setDescription(t('The transaction UUID.'))
      ->setReadOnly(TRUE);

    //borrowed from node->title
    $fields['description'] = FieldDefinition::create('string')
      ->setLabel(t('Description'))
      ->setDescription(t('A one line description of what was exchanged.'))
      ->setRequired(TRUE)
      ->setSettings(array('default_value' => '', 'max_length' => 255))
      ->setDisplayOptions(
        'view',
        array('label' => 'hidden', 'type' => 'string', 'weight' => -5)
      );

    $fields['serial'] = FieldDefinition::create('integer')
      ->setLabel(t('Serial number'))
      ->setDescription(t('Grouping of related transactions.'))
      ->setReadOnly(TRUE);

    $fields['parent'] = FieldDefinition::create('entity_reference')
      ->setLabel(t('Parent'))
      ->setDescription(t('Parent transaction that created this transaction.'))
      ->setSetting('target_type', 'mcapi_transaction')
      ->setSetting('default_value', 0)
      ->setReadOnly(TRUE)
      ->setRequired(TRUE);

    $fields['payer'] = FieldDefinition::create('entity_reference')
      ->setLabel(t('Payer'))
      ->setDescription(t('Id of the giving wallet'))
      ->setSetting('target_type', 'mcapi_wallet')
      ->setReadOnly(TRUE)
      ->setRequired(TRUE);

    $fields['payee'] = FieldDefinition::create('entity_reference')
      ->setLabel(t('Payee'))
      ->setDescription(t('Id of the receiving wallet'))
      ->setSetting('target_type', 'mcapi_wallet')
      ->setReadOnly(TRUE)
      ->setRequired(TRUE);

    $fields['type'] = FieldDefinition::create('entity_reference')
      ->setLabel(t('Type'))
      ->setDescription(t('The type/workflow path of the transaction'))
      ->setSetting('target_type', 'mcapi_type')
      ->setReadOnly(TRUE)
      ->setRequired(TRUE);

    $fields['state'] = FieldDefinition::create('entity_reference')
      ->setLabel(t('State'))
      ->setDescription(t('Completed, pending, disputed, etc.'))
      ->setSetting('target_type', 'mcapi_state')
      ->setReadOnly(FALSE)
      ->setRequired(TRUE);

    $fields['creator'] = FieldDefinition::create('entity_reference')
      ->setLabel(t('Creator'))
      ->setDescription(t('The user who created the transaction'))
      ->setSetting('target_type', 'user')
      ->setReadOnly(TRUE)
      ->setRequired(TRUE);

    $fields['created'] = FieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setDescription(t('The time that the transaction was created.'));

    $fields['exchange'] = FieldDefinition::create('entity_reference')
      ->setLabel(t('Exchange'))
      ->setDescription(t('The exchange governing this transaction.'))
      ->setSetting('target_type', 'mcapi_exchange')
      ->setReadOnly(TRUE)
      ->setRequired(TRUE);

    return $fields;
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
//    unset($child->worths);
    $payee_currency = reset($payee_currs);
    $payer_currency = reset($payer_currs);
    foreach ($this->worths as $worth) {
      $val = current($worth->getValue());
      if (isset($shared_currs[$val['currcode']])) {
        //simply copy the worth value
//TODO use the proper field API way to set the value of the field
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

  /**
   * {@inheritdoc}
   */
  //in parent, configEntityBase, $rel is set to edit-form by default - why would that be?
  //Is is assumed that every entity has an edit-form link? Any case this overrides it
  public function urlInfo($rel = 'canonical') {
    return parent::urlInfo($rel);
  }
}
