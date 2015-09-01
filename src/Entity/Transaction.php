<?php

/**
 * @file
 * Contains \Drupal\mcapi\Entity\Transaction.
 *
 * moduleHandler will surely be injected
 */

namespace Drupal\mcapi\Entity;

use Drupal\mcapi\TransactionInterface;

use Drupal\mcapi\Entity\Type;
use Drupal\mcapi\Entity\Wallet;
use Drupal\mcapi\McapiEvents;
use Drupal\mcapi\TransactionSaveEvents;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityConstraintViolationList;

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
 *       "12many" = "Drupal\mcapi\Form\One2Many",
 *       "many21" = "Drupal\mcapi\Form\Many2One",
 *       "admin" = "Drupal\mcapi\Form\TransactionForm",
 *     },
 *     "views_data" = "Drupal\mcapi\Views\TransactionViewsData",
 *     "route_provider" = {
 *       "html" = "Drupal\mcapi\Entity\TransactionRouteProvider",
 *     },
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
 *     "canonical" = "/transaction/{mcapi_transaction}"
 *   },
 *   constraints = {
 *     "TransactionIntegrity" = {}
 *   }
 * )
 */
class Transaction extends ContentEntityBase implements TransactionInterface {


  private $mailPluginManager;

  private $moduleHandler;

  //this is called from ContentEntityStorage::mapFromStorageRecords() so without create function
  public function __construct(array $values, $entity_type, $bundle = FALSE, $translations = array()) {
    parent::__construct($values, $entity_type, $bundle, $translations);
    $this->mailPluginManager = \Drupal::service('plugin.manager.mail');
    $this->moduleHandler = \Drupal::moduleHandler();//only used in transaction prevalidate alter hook
  }

  /**
   * {@inheritdoc}
   */
  public static function loadBySerials($serials) {
    $transactions = [];
    if ($serials) {
      //not sure which is faster, this or coding it using $this->filter()
      $results = \Drupal::entityManager()
        ->getStorage('mcapi_transaction')
        ->loadByProperties(['serial' => (array)$serials]);
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

    if (is_array($serials)) {
      return $transactions;
    }
    else return reset($transactions);
  }

  /**
   * {@inheritdoc}
   */
  public function label($langcode = NULL) {
    return t("Transaction #@serial", ['@serial' => $this->serial->value]);
  }

  /**
   * {@inheritdoc}
   */
  protected function urlRouteParameters($rel) {
    return [
      'mcapi_transaction' => $this->serial->value
    ];
  }

  /**
   * work on the transaction prior to validation
   *
   * @return NULL
   */
  public function preInsert() {

    //pass the transaction round to add children, but prevent the transaction from being modified.
    $clone = clone($this);
    $clone->children = [];
    \Drupal::service('event_dispatcher')->dispatch(
      McapiEvents::CHILDREN,
      new TransactionSaveEvents($clone)
    );
    $this->children = $clone->children;

    //allow any module to alter the transaction, with children, before validation.
    \Drupal::moduleHandler()->alter('mcapi_transaction', $this);
  }

  /**
   * Validate a transaction including children
   * The Drupal way has 'validate' and 'save' phases. We need to map those onto
   * this Transaction's needs which are 'add children', alter, validate, and save
   *
   * @return \Drupal\Core\Entity\EntityConstraintViolationListInterface
   *
   */
  function validate() {
    $violationList = $this->getTypedData()->validate();//@return \Symfony\Component\Validator\ConstraintViolationListInterface
    if ($this->parent->value == 0) {//just to be sure
      if (!$this->serial->value) {
        $this->preInsert();
      }
      foreach ($this->children as $entity) {
        foreach ($entity->validate() as $violation) {
          $violationList->add($violation);
        }
      }
    }
    return new EntityConstraintViolationList($this, iterator_to_array($violationList));
  }

  /**
   * {@inheritdoc}
   */
  public static function preCreate(EntityStorageInterface $storage, array &$values) {
    $values += [
      'serial' => 0,
      'type' => 'default',
      'description' => '',
      //'created' => REQUEST_TIME,
      'creator' => \Drupal::currentUser()->id(),//uid of 0 means drush must have created it
      'parent' => 0,
    ];

    $values['state'] = Type::load($values['type'])->start_state;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheTags() {
    // @todo Add bundle-specific listing cache tag? https://drupal.org/node/2145751
    return array_merge_recursive(
      ['mcapi_transaction:'. $this->id()],
      $this->payer->entity->getCacheTags(),
      $this->payee->entity->getCacheTags()
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts() {
    return ['user'];
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
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    //note that the worth field is field API because we can't define multiple cardinality fields in this entity.
    $fields['xid'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Transaction ID'))
      ->setDescription(t('The unique database key of the transaction'))
      ->setReadOnly(TRUE)
      ->setSetting('unsigned', TRUE);

    $fields['uuid'] = BaseFieldDefinition::create('uuid')
      ->setLabel(t('UUID'))
      ->setDescription(t('The transaction UUID.'))
      ->setReadOnly(TRUE);

    $fields['description'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Description'))
      ->setDescription(t('A one line description of what was exchanged.'))
      ->setRequired(TRUE)
      ->setSettings(array('default_value' => '', 'max_length' => 255))//this could be a setting.
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['serial'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Serial number'))
      ->setDescription(t('Grouping of related transactions.'))
      ->setReadOnly(TRUE);

    $fields['parent'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Parent xid'))
      ->setDescription(t('Parent transaction that created this transaction.'))
      ->setReadOnly(TRUE)
      ->setRequired(TRUE);

    $fields['payer'] = BaseFieldDefinition::create('wallet')
      ->setLabel(t('Payer'))
      ->setDescription(t('The wallet which was debited'))
      ->setSetting('target_type', 'mcapi_wallet')
      ->setReadOnly(TRUE)
      ->setRequired(TRUE)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE)
      ->setConstraints(['CanActOn' => ['action' => Wallet::OP_PAYIN]]);

    $fields['payee'] = BaseFieldDefinition::create('wallet')
      ->setLabel(t('Payee'))
      ->setDescription(t('The wallet which was credited'))
      ->setSetting('target_type', 'mcapi_wallet')
      ->setReadOnly(TRUE)
      ->setRequired(TRUE)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE)
      ->setConstraints(['CanActOn' => ['action' => Wallet::OP_PAYOUT]]);

    $fields['creator'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Creator'))
      ->setDescription(t('The user who created the transaction'))
      ->setSetting('target_type', 'user')
      ->setDisplayConfigurable('view', TRUE)
      ->setReadOnly(TRUE)
      ->setRevisionable(FALSE)
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
      ->setDisplayConfigurable('view', TRUE)
      ->setReadOnly(FALSE)
      ->setRequired(TRUE);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created on'))
      ->setDescription(t('The time that the transaction was created.'))
      ->setRevisionable(FALSE)
      ->setDisplayOptions(
        'view',
        ['label' => 'hidden', 'type' => 'timestamp', 'weight' => 0]
      )
      ->setDisplayOptions(
        'form',
        ['type' => 'datetime_timestamp', 'weight' => 10]
      )
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setDescription(t('The time that the transaction was last saved.'))
      ->setDisplayConfigurable('view', TRUE)
      ->setRevisionable(FALSE)
      ->setTranslatable(FALSE);

    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  function flatten($clone = TRUE) {
    if ($clone) {
      $transaction = clone($this);
    }
    else {
      $transaction = $this;
    }
    $flatarray = [$transaction];
    if (!empty($transaction->children)) {
      foreach ($transaction->children as $child) {
        $flatarray[] = $child;
      }
    }
    return $flatarray;
  }

  /**
   * Clear the cachetags of wallets involved in this transaction
   * @param type $update
   */
  protected function invalidateTagsOnSave($update) {
    parent::invalidateTagsOnSave($update);
    //ensure that we invalidate tags only once if a wallet is involved in more
    foreach ($this->flatten(FALSE) as $transaction) {
      foreach (['payer', 'payee'] as $actor) {
        $wallet = $transaction->{$actor}->entity;
        $wallets[$wallet->id()] = $wallet;//prevent duplicates
      }
    }
    foreach ($wallets as $wallet) {
      $wallet->invalidateTagsOnSave($update);
    }
  }

  /**
   * {@inheritdoc}
   * @todo write the undo function
   */
  function undo() {
    throw new \Exception('Transaction undo not implemented yet.');
    foreach ($this->flatten() as $subtransaction) {
      //get the currencies from $transaction->worth
      //foreach currency get the delete mode.
      //delete, or reverse each worth value, or change the state of the transaction
      //if there are no worth values left and parent += 0, then delete the transaction entity itself      
    }
    //for the parent, if there are no children and no currencies, then delete the transaction
  }
}
