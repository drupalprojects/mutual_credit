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
 *alter
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
 *     "canonical" = "mcapi.transaction_view"
 *   }
 * )
 */
class Transaction extends ContentEntityBase implements TransactionInterface {

  public $warnings = array();
  public $child = array();

  /**
   * {@inheritdoc}
   */
  public static function loadBySerials($serials) {
    $transactions = array();
    if ($serials) {
      //not sure which is faster, this or coding it using $this->filter()
      $results = \Drupal::entityManager()
        ->getStorage('mcapi_transaction')
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
    return $this->entityManager()->getStorage($this->entityTypeId)->save($this);
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
    $clone = clone($this);

    $this->children = array_merge(
      $this->moduleHandler->invokeAll('mcapi_transaction_children', array($clone)),
      $this->children
    );
  }

  /**
   * Validate a transaction cluster i.e. a transaction with children
   *
   * @return Violation[]
   */
  function validate() {
    $violations = array();

    if ($this->validated) return $violations;
    try{
      $this->preValidate();
    }
    catch(\Exception $e) {
      $violations[] = $e->getMessage();
    }
    //all this does is check the field values against the field definitions.
    //Don't know how to include the worth field in that
    if ($this->isNew()) {
      //check that the uuid doesn't already exist. i.e. that we are not resubmitting the same transactions
      $properties = array('uuid' => $this->get('uuid')->value);
      if (entity_load_multiple_by_properties('mcapi_transaction', $properties)) {
        $violations['uuid'] = t('Transaction has already been inserted: @uuid', array('@uuid' => $this->uuid->value));
      }
      //ensure the transaction is in the right starting state
      $start_state_id = $this->type->entity->start_state;
      $this->state->setValue($start_state_id);
      foreach ($this->children as $transaction) {
        //child transactions inherit the state from the parent, regardless of the child type
        $transaction->state->setValue($start_state_id);
        $transaction->parent->value = -1;//this will be set after the parent is saved
      }
    }
    $transactions = $this->flatten();//NB $transactions contains clones of the $this
    $violations = self::validateTransaction(array_shift($transactions));
    $child_warnings = array();
    foreach ($transactions as $transaction) {
      $child_warnings += self::validateTransaction($transaction);
    }
    //note that this validation cannot change the original transaction because a clone of it is passed
    $violations += \Drupal::moduleHandler()->invokeAll('mcapi_transaction_validate', array(array(clone($this))));

    if ($child_warnings) {
      $child_errors = \Drupal::config('mcapi.misc')->get('child_errors');

      foreach ($child_warnings as $e) {
        if (!$child_errors['allow']) {
          //actually these should be handled as violations or warnings
          $violations[] = $e->getMessage();
        }
        else {
          $this->warnings[] = $e->getMessage();
        }

        if ($child_errors['log']){
          //TODO will there be an mcapi logger?
          \Drupal::logger('mcapi')->error(
            t("transaction failed validation with the following messages: !messages", array('!messages' => implode(' ', $warnings)))
          );
        }
        if ($child_errors['mail_user1']){
          //should this be done using the drupal mail system? my life is too short for such a rigmarole
          mail(
            User::load(1)->mail,
            t('Child transaction error on !site', array('!site' => \Drupal::config('system.site')->get('name'))),
            implode('\n', $warnings)
          );
        }
      }
    }
    //$violations = parent::validate();//doesn't exist
    $violation_messages = array();
    foreach ($violations as $violation) {
      //print_r($violation);print_r($this->created->getValue());die();
      /* methods
      [1] => __toString
      [2] => getMessageTemplate
      [3] => getMessageParameters
      [4] => getMessagePluralization
      [5] => getMessage
      [6] => getRoot
      [7] => getPropertyPath
      [8] => getInvalidValue
      [9] => getCode
      (*/
      //TEMP violations are just strings for now.
      $violation_messages[] = $violation;//TODO this is temp really we want (string)$violation
    }
    $this->validated = TRUE;
    //TODO check that transaction limit violations highlight the right currency field
    return $violation_messages; //temp need to tidy this up
  }

  /**
   * validate a transaction entity i.e. 1 ledger entry
   * There should be no children!
   *
   * @param Transaction $transaction
   *
   * @return violation[]
   *
   * @todo Decide whether this should be static or not
   */
  private static function validateTransaction($transaction) {
    $violations = array();
    //check the payer and payee aren't the same
    if ($transaction->payer->target_id == $transaction->payee->target_id) {
      $violations['payee'] = t('Wallet @num cannot pay itself.', array('@num' => $transaction->payer->target_id));
    }
    //check that the current user is permitted to pay out and in according to the wallet permissions
    $walletAccess = \Drupal::entityManager()->getAccessControlHandler('mcapi_wallet');
    if (!$walletAccess->checkAccess($transaction->payer->entity, 'payout', NULL, \Drupal::currentUser())) {
      $violations['payer'] = t('You are not allowed to make payments from this wallet');
    }
    if (!$walletAccess->checkAccess($transaction->payee->entity, 'payout', NULL, \Drupal::currentUser())) {
      $violations['payee'] = t('You are not allowed to pay into this wallet');
    }
    //check that the description isn't too long
    $length = strlen($transaction->description->value);
    if ($length > MCAPI_MAX_DESCRIPTION) {
      $violations['payee'] = t(
        'The description field is @num chars longer than the @max maximum',
        array('@num' => $strlen-$max, '@max' => MCAPI_MAX_DESCRIPTION)
      );
    }
    //is it worth checking whether all the entity properties and fields are actually fieldItemLists?
    return $violations;
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
      'children' => array(),
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
        $transaction->children = array();
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
    ->setLabel(t('Created'))
    ->setDescription(t('The time that the transaction was created.'))
    ->setTranslatable(TRUE)
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
