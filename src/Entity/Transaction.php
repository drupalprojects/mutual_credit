<?php

/**
 * @file
 * Contains \Drupal\mcapi\Entity\Transaction.
 * @todo on all entity types sort out the linkTemplates https://drupal.org/node/2221879
 */

namespace Drupal\mcapi\Entity;

const MCAPI_MAX_DESCRIPTION = 255;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Field\FieldDefinition;
use Drupal\mcapi\McapiTransactionException;
use Drupal\mcapi\Plugin\Field\McapiTransactionWorthException;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\mcapi\Access\WalletAccessController;
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
 *   base_table = "mcapi_transaction",
 *   controllers = {
 *     "storage" = "Drupal\mcapi\Storage\TransactionStorage",
 *     "view_builder" = "Drupal\mcapi\ViewBuilder\TransactionViewBuilder",
 *     "access" = "Drupal\mcapi\Access\TransactionAccessController",
 *     "form" = {
 *       "transition" = "Drupal\mcapi\Form\TransitionForm",
 *       "masspay" = "Drupal\mcapi\Form\MassPay",
 *       "admin" = "Drupal\mcapi\Form\TransactionForm",
 *       "delete" = "Drupal\mcapi\Form\TransactionDeleteConfirm",
 *     },
 *   },
 *   admin_permission = "configure mcapi",
 *   entity_keys = {
 *     "id" = "xid",
 *     "uuid" = "uuid",
 *     "name" = "description"
 *   },
 *   fieldable = TRUE,
 *   translatable = FALSE,
 *   links = {
 *     "canonical" = "mcapi.transaction_view",
 *     "admin-form" = "mcapi.admin.transactions"
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
   */
  public function save() {
    $this->moduleHandler = \Drupal::moduleHandler();
    $this->preValidate();
    $storage = $this->entityManager()->getStorage($this->entityTypeId);

    $this->moduleHandler->alter('mcapi_transaction', $this);
    if ($violations = $this->validate()) {
      //TODO put all the violations in here, somehow
      reset($violations);
      //throw new McapiTransactionException(key($violations), current($violations)->getMessage());
      throw new McapiTransactionException(key($violations), current($violations) );
    }

    return $storage->save($this);
  }


  public function prevalidate() {
    $clone = clone($this);
    $this->children = $this->moduleHandler->invokeAll('mcapi_transaction_children', array($clone));
    unset($clone); //save memory coz validation could be intense!

  }

  /**
   * Validate a transaction cluster i.e. a transaction with children
   *
   * @return Violation[]
   */
  function validate() {
    //all this does is check the field values against the field definitions.
    //Don't know how to include the worth field in that
    $violations = array();
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
        $transaction->state->setValue($start_state_id);
        $transaction->parent->value = -1;//this will be set after the parent is saved
      }
    }
    $transactions = $this->flatten();//NB $transactions contains clones of the $this
    $violations = self::validateTransaction(array_shift($transactions));
    $child_warnings = array();
    foreach ($transactions as $transaction) {
      $child_warnings += self::validateTransaction($child);
    }

    //note that this validation cannot change the original transaction because a clone of it is passed
    $violations += $this->moduleHandler->invokeAll('mcapi_transaction_validate', array(array(clone($this))));

    if ($warnings = array_filter($child_warnings)) {
      $child_errors = \Drupal::config('mcapi.misc')->get('child_errors');

      foreach ($warnings as $e) {
        if (!$child_errors['allow']) {
          //actually these should be handled as violations or warnings
          $violations[] = $e->getMessage();
        }
        else {
          drupal_set_message($e->getMessage(), 'warning');
        }

        $replacements = array(
          '!messages' => implode(' ', $warnings),
        );
        if ($child_errors['log']){
          //TODO will there be an mcapi logger?
          \Drupal::logger('mcapi')->error(
            t("transaction failed validation with the following messages: !messages", array('!messages' => $message))
          );
        }
        if ($child_errors['mail_user1']){
          //should this be done using the drupal mail system? my life is too short for that rigmarole
          mail(
            User::load(1)->mail,
            t('Child transaction error on !site', array('!site' => \Drupal::config('system.site')->get('name'))),
            $replacements['!messages'] ."\n\n". $replacements['!dump']
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
    $walletAccess = \Drupal::entityManager()->getAccessController('mcapi_wallet');
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
      $tags1 = $t->payer->entity->getCacheTag();
      $tags2 = $t->payee->entity->getCacheTag();
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
      ->setSettings(array('default_value' => '', 'max_length' => MCAPI_MAX_DESCRIPTION));

    $fields['serial'] = FieldDefinition::create('integer')
      ->setLabel(t('Serial number'))
      ->setDescription(t('Grouping of related transactions.'))
      ->setReadOnly(TRUE);

    $fields['parent'] = FieldDefinition::create('integer')
      ->setLabel(t('Parent xid'))
      ->setDescription(t('Parent transaction that created this transaction.'))
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
      ->setDescription(t('The time that the transaction was created.'))
      ->setTranslatable(TRUE);

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

}
