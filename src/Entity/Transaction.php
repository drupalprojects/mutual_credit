<?php

/**
 * @file
 * Contains \Drupal\mcapi\Entity\Transaction.
 * @todo on all entity types sort out the linkTemplates https://drupal.org/node/2221879
 */

namespace Drupal\mcapi\Entity;

use Drupal\mcapi\TransactionInterface;

const MCAPI_MAX_DESCRIPTION = 255;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\mcapi\McapiTransactionException;
use Drupal\mcapi\Plugin\Field\McapiTransactionWorthException;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\mcapi\Access\WalletAccessControlHandler;
use Drupal\user\Entity\User;
use Drupal\mcapi\Entity\State;

/**
 * Defines the Transaction entity.
 * Transactions are grouped and handled by serial number
 * but the unique database key is the xid.
 * 
 * @ContentEntityType(
 *   id = "mcapi_transaction",
 *   label = @Translation("Transaction"),
 *   handlers = {
 *     "storage" = "Drupal\mcapi\Storage\TransactionStorage",
 *     "storage_schema" = "Drupal\mcapi\Storage\TransactionStorageSchema",
 *     "view_builder" = "Drupal\mcapi\ViewBuilder\TransactionViewBuilder",
 *     "access" = "Drupal\mcapi\Access\TransactionAccessControlHandler",
 *     "form" = {
 *       "transition" = "Drupal\mcapi\Form\TransitionForm",
 *       "masspay" = "Drupal\mcapi\Form\MassPay",
 *       "admin" = "Drupal\mcapi\Form\TransactionForm",
 *       "delete" = "Drupal\mcapi\Form\TransactionDeleteConfirm",
 *     },
 *     "views_data" = "Drupal\mcapi\Views\TransactionViewsData"
 *   },
 *   admin_permission = "configure mcapi",
 *   base_table = "mcapi_transaction",
 *   entity_keys = {
 *     "id" = "xid",
 *     "uuid" = "uuid",
 *     "name" = "description"
 *   },
 *   field_ui_base_route = "mcapi.admin.transactions",
 *   translatable = FALSE,
 *   links = {
 *     "canonical" = "entity.mcapi_transaction.canonical"
 *   }
 * )
 */
class Transaction extends ContentEntityBase implements TransactionInterface {

  public $warnings = [];
  private $children = [];

  /**
   * {@inheritdoc}
   */
  public static function loadBySerials($serials) {
    $transactions = [];
    if ($serials) {
      //not sure which is faster, this or coding it using $this->filter()
      $results = \Drupal::entityManager()->getStorage('mcapi_transaction')
        ->loadByProperties(array('serial' => (array)$serials));
      //put all the transaction children under the parents
      foreach ($results as $xid => $transaction) {
        if ($pxid = $transaction->get('parent')->value) {
          $results[$pxid]->children[] = $transaction;
          unset($results[$xid]);
        }
      }
      //I had a problem on uninstalling the tester module that all the parents were somehow deleted already
      //which rather screwed up this function.
      //change the array keys for the serial numbers
      foreach ($results as $transaction) {
        $transactions[$transaction->serial->value] = $transaction;
      }
    }

    if (is_array($serials)) return $transactions;
    else return reset($transactions);
  }

  /**
   * {@inheritdoc}
   */
  public function label($langcode = NULL) {
    return t("Transaction #@serial", array('@serial' => $this->get('serial')->value));
  }

  /**
   * {@inheritdoc}
   */
  protected function urlRouteParameters($rel) {
    return array(
      'mcapi_transaction' => $this->serial->value
    );
  }


  /**
   * {@inheritdoc}
   * does the same as Entity::save but validates first.
   */
  public function save() {
    if (!$this->validated) {
      if ($violations = $this->validate()) {
        //TODO put all the violations in here, somehow
        reset($violations);
        //throw new McapiTransactionException(key($violations), current($violations)->getMessage());
        throw new McapiTransactionException(key($violations), current($violations) );
      }
    }
    return $this->entityManager()->getStorage('mcapi_transaction')->save($this);
  }

  /**
   * work on the transaction prior to validation
   * 
   * @return NULL
   * 
   * @todo handle errors coming from the hook implementations
   * 
   * @throws McapiException
   */
  public function prevalidate() {
    $this->moduleHandler = \Drupal::moduleHandler();
    $this->moduleHandler->alter('mcapi_transaction', $this);
    
    //ensure the transaction is in the right starting state
    if($this->isNew()) {
      $start_state_id = $this->type->entity->start_state;
      $this->state->setValue($start_state_id);
    }    
    $clone = clone($this);
    $this->children = array_merge(
      $this->moduleHandler->invokeAll('mcapi_transaction_children', array($clone)),
      $this->children
    );
  }

  /**
   * Validate a transaction cluster i.e. a transaction with children
   * @todo there seens to be no precedent for whole-entity validation in drupal
   * so there's no format for handling errors outside $form and typedata violations
   * 
   * @return Violation[]
   */
  function validate() {
    //if this is coming from transactionForm then all the entiry fields are validated
    $child_exceptions = $exceptions = $violations = [];
    if ($this->validated) {
      return [];
    }
    try{
      $this->preValidate();
    }
    catch(\Exception $e) {
      $exceptions[] = $e;
    }
    $cloned = $this->flatten();
    
    //note that this validation cannot change the original transaction because a clone of it is passed
    $exceptions = array_merge(
      $exceptions, 
      \Drupal::moduleHandler()->invokeAll('mcapi_transaction_validate', array($cloned))
    );
    
    if (!$this->typedDataValidated) {
      $violations = $this->validateTypedData();
    }
    
    //validate the parent and children separately in case the errors are handled differently
    $exceptions = array_merge(
      $exceptions, 
      self::validateTransaction(array_shift($cloned))//top level
    );
    foreach ($cloned as $child_transaction) {
      $violations = array_merge($violations, parent::validate($child_transaction));
      $child_exceptions = array_merge($child_exceptions, self::validateTransaction($child_transaction));
    }
    if ($child_exceptions) {
      $settings = \Drupal::config('mcapi.misc')->get('child_errors');

      if ($settings['allow']) {
        $this->warnings = $child_exceptions;
      }
      else {
        $exceptions = array_merge($exceptions, $child_exceptions);
      }
      if ($settings['log']){
        //TODO will there be an mcapi logger?
        \Drupal::logger('mcapi')->error(
          t(
            "transaction failed validation with the following messages: !messages", 
            ['!messages' => implode(' ', $this->warnings)]
          )
        );
      }
      if ($settings['mail_user1']){
        $message[] = $child_errors['allow'] ? 
          t('Transaction was allowed.') : 
          t('Transaction was blocked');
        foreach ($this->warnings as $line) $message[] = $line;
        $message[] = print_r($this, 1);
        //should this be done using the drupal mail system? my life is too short for such a rigmarole
        mail(
          User::load(1)->mail,
          t(
            'Child transaction error on !site', 
            ['!site' => \Drupal::config('system.site')->get('name')]
          ),
          implode('\n', $message)//text of the message
        );
      }
    }
    
    //we're actually supposed only to return violations, 
    //but the Exceptions also have the same getMessage method
    return array_merge($violations, $exceptions);
  }
  
  /**
   * TEMP
   * Validate the typedData but outside of the form handler
   * @see EntityFormDisplay::validateFormValues
   */
  private function validateTypedData() {
    $violations = [];
    foreach ($this as $field_name => $items) {
      foreach ($items->validate() as $violation) {
        $violations[] = $violation;
      }
    }
    $this->typedDataValidated = TRUE;//temp flag
    return $violations;
  }

  /**
   * validate a transaction entity i.e. 1 ledger entry level
   * There should be no children!
   *
   * @param Transaction $transaction
   *
   * @return violation[]
   *
   * @todo Decide whether this should be static or not
   */
  private static function validateTransaction($entity) {
    $exceptions = [];
    if ($entity->isNew()) {
      //check that the uuid doesn't already exist. i.e. that we are not resubmitting the same transactions
      if (entity_load_multiple_by_properties('mcapi_transaction', ['uuid' => $entity->uuid->value])) {
        $exceptions[] = new McapiTransactionException(
          'uuid',
          t('Transaction has already been inserted: @uuid', ['@uuid' => $entity->uuid->value])
        );
      }
    }
    //check the payer and payee aren't the same
    if ($entity->payer->target_id == $entity->payee->target_id) {
      $exceptions[] = new McapiTransactionException(
        'payee',
        t('Wallet @num cannot pay itself.', array('@num' => $entity->payer->target_id))
      );
    }
    //check that the current user is permitted to pay out and in according to the wallet permissions
    $walletAccess = \Drupal::entityManager()->getAccessControlHandler('mcapi_wallet');
    if (!$walletAccess->checkAccess($entity->payer->entity, 'payout', NULL, \Drupal::currentUser())) {
      $exceptions[] = new McapiTransactionException(
        'payer', 
        t('You are not allowed to make payments from this wallet.')
      );
    }
    if (!$walletAccess->checkAccess($entity->payee->entity, 'payout', NULL, \Drupal::currentUser())) {
      $exceptions[] = new McapiTransactionException(
        'payee', 
        t('You are not allowed to pay into this wallet.')
      );
    }
    //check that the description isn't too long
    $length = strlen($entity->description->value);
    if ($length > MCAPI_MAX_DESCRIPTION) {
      $exceptions[] = new McapiTransactionException('description', t(
        'The description field is @num chars longer than the @max maximum',
        ['@num' => $strlen-$max, '@max' => MCAPI_MAX_DESCRIPTION]
      ));
    }
    return $exceptions;
  }

  /**
   * {@inheritdoc}
   */
  public static function preCreate(EntityStorageInterface $storage, array &$values) {
    $values += array(
      'serial' => 0,
      'type' => 'default',
      'description' => '',
      'created' => REQUEST_TIME,
      'creator' => \Drupal::currentUser()->id(),//uid of 0 means drush must have created it
      'parent' => 0,
      'payer' => 1,//chances are that these exist
      'payee' => 2,
      'children' => [],
      'exchange' => 1
    );
  }

  /**
   * save the child transactions, which refer to the saved parent
   *
   * @todo clear the entity cache of all wallets involved in this transaction and its children
   *   because the wallet entity cache contains the balance limits and the summary stats
   */
  public function postSave(EntityStorageInterface $storage_controller, $update = TRUE) {
    parent::postSave($storage_controller, $update);
    self::clearWalletCache($this);
  }

  public static function postDelete(EntityStorageInterface $storage_controller, array $entities) {
    parent::postDelete($storage_controller, $entities);
    foreach ($entities as $entity) {
      self::clearWalletCache($entity);
    }
  }

  public function erase() {
    $this->entityManager()->getStorage('mcapi_transaction')->doErase(array($this));
  }

  //also needs doing when a wallet changes ownership
  //should this be in the interface??
  private static function clearWalletCache($transaction) {
    foreach ($transaction->flatten() as $t) {
      $tags1 = $t->payer->entity->getCacheTags();
      $tags2 = $t->payee->entity->getCacheTags();
    }
    \Drupal\Core\Cache\Cache::invalidateTags(array_merge_recursive($tags1, $tags2));//this isn't tested
  }

  /**
   * Ensure parent transactions have a 'children' property
   */
  public static function postLoad(EntityStorageInterface $storage_controller, array &$entities) {
    foreach ($entities as $transaction) {
      if ($transaction->parent->value == 0) {
        $transaction->children = [];
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function transition($transition, array $values) {
    $context = array(
      'values' => $values,
      'old_state' => $this->get('state')->value,
      'config' => $transition->getConfiguration(),
    );

    //any problems need to be thrown
    $renderable = $transition->execute($this, $context)
    or $renderable = 'transition returned nothing renderable';

    //notify other modules, especially rules.
    $renderable += \Drupal::moduleHandler()->invokeAll('mcapi_transition', array($this, $transition, $context));

    return $renderable;
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    //note that the worth field is field API because we can't define multiple cardinality fields in this entity.
    $fields['xid'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Transaction ID'))
      ->setDescription(t('The unique transaction ID.'))
      ->setReadOnly(TRUE)
      ->setSetting('unsigned', TRUE);

    $fields['uuid'] = BaseFieldDefinition::create('uuid')
      ->setLabel(t('UUID'))
      ->setDescription(t('The transaction UUID.'))
      ->setReadOnly(TRUE);

    //borrowed from node->title
    $fields['description'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Description'))
      ->setDescription(t('A one line description of what was exchanged.'))
      ->setRequired(TRUE)
      ->setSettings(array('default_value' => '', 'max_length' => MCAPI_MAX_DESCRIPTION));

    $fields['serial'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Serial number'))
      ->setDescription(t('Grouping of related transactions.'))
      ->setReadOnly(TRUE);

    $fields['parent'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Parent xid'))
      ->setDescription(t('Parent transaction that created this transaction.'))
      ->setReadOnly(TRUE)
      ->setRequired(TRUE);

    $fields['payer'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Payer'))
      ->setDescription(t('Id of the giving wallet'))
      ->setSetting('target_type', 'mcapi_wallet')
      ->setReadOnly(TRUE)
      ->setRequired(TRUE);

    $fields['payee'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Payee'))
      ->setDescription(t('Id of the receiving wallet'))
      ->setSetting('target_type', 'mcapi_wallet')
      ->setReadOnly(TRUE)
      ->setRequired(TRUE);

    $fields['type'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Type'))
      ->setDescription(t('The type/workflow path of the transaction'))
      ->setSetting('target_type', 'mcapi_type')
      ->setReadOnly(TRUE)
      ->setRequired(TRUE);

    $fields['state'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('State'))
      ->setDescription(t('Completed, pending, disputed, etc.'))
      ->setSetting('target_type', 'mcapi_state')
      ->setReadOnly(FALSE)
      ->setRequired(TRUE);

    $fields['creator'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Creator'))
      ->setDescription(t('The user who created the transaction'))
      ->setSetting('target_type', 'user')
      ->setReadOnly(TRUE)
      ->setRequired(TRUE);

    $fields['created'] = BaseFieldDefinition::create('created')
    ->setLabel(t('Created on'))
    ->setDescription(t('The time that the transaction was created.'))
    ->setRevisionable(FALSE)
    ->setTranslatable(FALSE)
    ->setDisplayOptions('view', array(
      'label' => 'hidden',
      'type' => 'timestamp',
      'weight' => 0,
    ))
    ->setDisplayOptions('form', array(
      'type' => 'datetime_timestamp',
      'weight' => 10,
    ))
    ->setDisplayConfigurable('form', TRUE);

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setDescription(t('The time that the transaction was last saved.'))
      ->setTranslatable(TRUE);

    //TODO in beta2, this field is required by views. Delete if pos
    /*
    $fields['langcode'] = BaseFieldDefinition::create('language')
    ->setLabel(t('Language code'))
    ->setDescription(t('language code.'))
    ->setSettings(array('default_value' => 'und'));
    */
    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  function flatten() {
    $clone = clone($this);
    $flatarray = array($clone);
    if (!empty($clone->children)) {
      foreach ($clone->children as $child) {
        $flatarray[] = $child;
      }
    }
    unset($clone->children);
    return $flatarray;
  }

  /**
   * Override Entity.php to update wallets as well as transaction
   * @param type $update
   */
  protected function invalidateTagsOnSave($update) {
    parent::invalidateTagsOnSave($update);
    $walletStorage = $this->entityManager()->getStorage('mcapi_wallet');
    foreach ($this->flatten() as $transaction) {
      foreach (['payer', 'payee'] as $actor) {
        $wallet = $transaction->{$actor}->getEntity()->invalidateTagsOnSave();
      }
    }
  }


  /**
   * calculate a transaction quantity based on a provided formala and input quantity
  *
  * @param string $formula
  *   formula using [q] for base_quant. If it is just a number the number is returned as is
  *   otherwise [q]% should work or other variables to be determined
  *
  * @param integer $base_value
  *
  * @return interger | NULL
  */
  public static function calc($formula, $base_value) {
    if (is_null($base_value)) return 0;
    if (is_numeric($formula)) return $formula;
    $proportion = str_replace('%', '', $formula);
    if (empty($base_value)) $base_quant = 0;
    if (is_numeric($proportion)) {
      return $base_value * $proportion/100;
    }
    //$formula = str_replace('/', '//', $formula);
    $equation = str_replace('[q]', $base_value, $formula) .';';
    $val = eval('return '. $equation);
    if (is_numeric($val)) return $val;
    drupal_set_message(t('Problem with calculation for dependent transaction: @val', array('@val' => $val)));
  }
}
