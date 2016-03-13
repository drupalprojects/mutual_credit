<?php

/**
 * @file
 * Contains \Drupal\mcapi\Entity\Transaction.
 */

namespace Drupal\mcapi\Entity;

use Drupal\mcapi\Entity\TransactionInterface;

use Drupal\mcapi\Entity\Type;
use Drupal\mcapi\McapiEvents;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\EntityTypeInterface;

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
 *     "list_builder" = "\Drupal\mcapi\ListBuilder\TransactionListBuilder",
 *     "access" = "Drupal\mcapi\Access\TransactionAccessControlHandler",
 *     "form" = {
 *       "operation" = "Drupal\mcapi\Form\OperationForm",
 *       "12many" = "Drupal\mcapi\Form\One2Many",
 *       "many21" = "Drupal\mcapi\Form\Many2One",
 *       "admin" = "Drupal\mcapi\Form\TransactionForm",
 *       "edit" = "Drupal\mcapi\Form\TransactionEditForm",
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
 *   field_ui_base_route = "view.mcapi_transactions.admin",
 *   translatable = FALSE,
 *   links = {
 *     "canonical" = "/transaction/{mcapi_transaction}",
 *     "edit-form" = "/transaction/{mcapi_transaction}/edit",
 *     "collection" = "/admin/accounting/transactions"
 *   },
 *   constraints = {
 *     "DifferentWallets" = {},
 *     "CommonCurrency" = {}
 *   }
 * )
 */
class Transaction extends ContentEntityBase implements TransactionInterface, EntityChangedInterface {

  use EntityChangedTrait;

  private $moduleHandler;
  private $eventDispatcher;

  public function __construct(array $values, $entity_type, $bundle = FALSE, $translations = array()) {
    parent::__construct($values, $entity_type, $bundle, $translations);
    //there seems to be no way to inject anything here
    $this->moduleHandler = \Drupal::moduleHandler();
    $this->eventDispatcher = \Drupal::service('event_dispatcher');
  }

  /**
   * {@inheritdoc}
   */
  public static function loadBySerial($serial) {
    $children = [];
    //not sure which is best, this or entityQuery. This is shorter and faster.
    $results = \Drupal::entityTypeManager()
      ->getStorage('mcapi_transaction')
      ->loadByProperties(['serial' => $serial]);
    //put all the transaction children under the parents
    foreach ($results as $xid => $entity) {
      if ($entity->get('parent')->value) {
        $children[] = $entity;
      }
      else{
        $transaction = $entity;
      }
    }
    $transaction->children = $children;
    return $transaction;
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
   * Add children to the transaction or make other changes
   * note this must be called explicitly, and PRIOR to validation
   *
   * @return NULL
   */
  public function assemble() {
    //prevent assembly of child transactions, which could cause infinite recursion
    if ($this->parent->value) {
      return;
    }
    //pass the transaction round to add children, but prevent the transaction from being modified.
    $clone = clone($this);
    $clone->children = [];
    //this hook prevents altering the original transaction but only allows adding of children
    $event = new \Drupal\mcapi\Event\TransactionAssembleEvent($clone);
    $this->eventDispatcher->dispatch(McapiEvents::ASSEMBLE, $event);
    $this->children = $clone->children;
    //allow any module to alter the transaction, with children, before validation.
    \Drupal::moduleHandler()->alter('mcapi_transaction_assemble', $this);

    foreach ($this->children as $child) {
      $child->parent->value = 1;//temp value because parent has no xid yet
    }
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
    $violationList = parent::validate();
    //only act on the parent just to be sure.
    if ($this->parent->value == 0) {
      //if the transaction hasn't been assembled, it won't have a children property
      foreach ((array)$this->children as $entity) {
        foreach ($entity->validate() as $violation) {
          throw new \Exception ('Violation in child transaction, '.$violation->getPropertyPath() .': '.$violation->getMessage());
        }
      }
    }
    return $violationList;
  }

  /**
   * {@inheritdoc}
   */
  public static function preCreate(EntityStorageInterface $storage, array &$values) {
    $values += [
      'type' => 'default',
      'creator' => \Drupal::currentUser()->id(),//uid of 0 means drush must have created it
    ];
    $type = Type::load($values['type']);
    if (!$type) {
      throw new \Exception(t('Cannot create transaction of unknown type: @type', ['@type' => $values['type']]));
    }
    $values['state'] = $type->start_state;
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
      ->setDefaultValue('')
      ->setSettings(['max_length' => 255])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('form', ['type' => 'string_textfield',  'weight' => 3])
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayOptions('view', ['type' => 'string',  'weight' => 3]);

    $fields['serial'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Serial number'))
      ->setDescription(t('Grouping of related transactions.'))
      ->setReadOnly(TRUE);

    //this is really an entity reference
    $fields['parent'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Parent xid'))
      ->setDescription(t('Parent transaction that created this transaction.'))
      ->setDefaultValue(0)
      ->setReadOnly(TRUE)
      ->setRequired(TRUE);

    //I wanted to add the CanPayout and CanPayin constraints in the widget builder
    //but I couldn't see how. So at the moment they apply to all entity forms, but
    //they only run code when $items->restriction is TRUE
    $fields['payer'] = BaseFieldDefinition::create('wallet_reference')
      ->setLabel(t('Payer'))
      ->setDescription(t('The giving wallet'))
      ->setReadOnly(TRUE)
      ->setRequired(TRUE)
      ->addConstraint('CanPayout')
      ->setCardinality(1)
      ->setSetting('handler_settings', ['op' => 'payout'])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('form', ['type' => 'wallet_reference_autocomplete', 'weight' => 1])
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayOptions('view', ['type' => 'entity_reference_label', 'weight' => 1]);

    $fields['payee'] = BaseFieldDefinition::create('wallet_reference')
      ->setLabel(t('Payee'))
      ->setDescription(t('The receiving wallet'))
      ->setReadOnly(TRUE)
      ->setRequired(TRUE)
      ->addConstraint('CanPayin')
      ->setCardinality(1)
      ->setSetting('handler_settings', ['op' => 'payin'])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('form', ['type' => 'wallet_reference_autocomplete', 'weight' => 2])
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayOptions('view', ['type' => 'entity_reference_label', 'weight' => 2]);

    $fields['creator'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Creator'))
      ->setDescription(t('The user who created the transaction'))
      ->setSetting('target_type', 'user')
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayConfigurable('form', TRUE)
      ->setReadOnly(TRUE)
      ->setRevisionable(FALSE)
      ->setRequired(TRUE);

    $fields['type'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Type'))
      ->setDescription(t('The type/workflow path of the transaction'))
      ->setSetting('target_type', 'mcapi_type')
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayConfigurable('form', TRUE)
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
        if (!$wallet) continue;//this should never happen, but does
        $wallets[$wallet->id()] = $wallet;//prevent duplicates
      }
    }
    foreach ($wallets as $wallet) {
      $wallet->invalidateTagsOnSave($update);
    }
  }
}
