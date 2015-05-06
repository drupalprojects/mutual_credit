<?php

/**
 * @file
 * Contains \Drupal\mcapi\Entity\Transaction.
 *
 * moduleHandler will surely be injected
 */

namespace Drupal\mcapi\Entity;

use Drupal\mcapi\TransactionInterface;

use Drupal\mcapi\Access\WalletAccessControlHandler;
use Drupal\mcapi\Entity\State;
use Drupal\mcapi\McapiEvents;
use Drupal\mcapi\TransactionSaveEvents;
use Drupal\mcapi\McapiTransactionException;
use Drupal\mcapi\Plugin\Field\McapiTransactionWorthException;
use Drupal\user\Entity\User;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Cache\Cache;
use Symfony\Component\EventDispatcher\EventDispatcher;

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
 *     "canonical" = "transaction/{mcapi_transaction}"
 *   },
 *   constraints = {
 *     "integrity" = "Drupal\mcapi\TransactionIntegrityConstraint"
 *   }
 * )
 */
class Transaction extends ContentEntityBase implements TransactionInterface {

  /**
   * these objects have a method getMessage which is used for a form error
   *
   * @var array
   *
   * @todo remove this in beta10
   */
  public $violations = [];

  /**
   * These are the secondary transactions in the cluster. Identical to the $this
   * but they will have no children
   *
   * @var Transaction[]
   */
  private $children = [];

  private $eventDispatcher;
  private $mailPluginManager;

  //this is called from ContentEntityStorage::mapFromStorageRecords() so without create function
  public function __construct(array $values, $entity_type, $bundle = FALSE, $translations = array()) {
    parent::__construct($values, $entity_type, $bundle, $translations);
    $this->eventDispatcher = \Drupal::service('event_dispatcher');
    $this->mailPluginManager = \Drupal::service('plugin.manager.mail');
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
   * {@inheritdoc}
   * does the same as Entity::save but validates first.
   */
  public function save() {
    if (!$this->validated) {
      if ($violations = $this->validate()) {
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
   * @throws McapiException
   */
  public function prevalidate() {
    //ensure the transaction is in the right starting state
    if($this->isNew()) {
      $start_state_id = $this->type->entity->start_state;
      $this->state->setValue($start_state_id);
    }
    $new_children = \Drupal::service('event_dispatcher')->dispatch(
      McapiEvents::CHILDREN,
      new TransactionSaveEvents(clone($this), $context)
    );
    array_merge($this->children, $new_children);

    //Alter hooks should throw an McapiException
    \Drupal::moduleHandler()->alter('mcapi_transaction', $this);
  }

  /**
   * Validate a transaction cluster i.e. a transaction with children
   * so there's no format for handling errors outside $form and typedata violations
   *
   * @return \Symfony\Component\Validator\ConstraintViolationInterface[]
   *
   * @throws \Exception
   *
   * @todo after beta10
   * @see https://www.drupal.org/node/2015613.
   */
  function validate() {
    //if this is coming from transactionForm then all the entiry fields are validated
    $violations = [];
    $this->errors = [];
    if ($this->validated) {
      return [];
    }
    try{
      $this->preValidate();
      if (!$this->typedDataValidated) {
        foreach ($this->flatten() as $entity) {
          $violations = array_merge(
            $violations,
            $entity->validateTypedData()
          );
        }
      }
    }
    catch(\Exception $e) {
      if ($settings['mail_user1']){
        foreach ($this->errors as $line) {
          $message[] = $line;
        }
        $message[] = print_r($this, 1);
        //should this be done using the drupal mail system?
        //my life is too short for such a rigmarol
        $context = [
          'subject' =>  t(
            'Transaction error on !site',
            ['!site' => \Drupal::config('system.site')->get('name')]
          ),
          'message' => implode('\n', $message)
        ];

        $this->mailPluginManager->mail(
          'system',
          'action_send_email',
          User::load(1)->mail,
          $langcode,
          ['context' => $context]
        );
      }
      //TEMP
      drupal_set_message($e->getMessage(), 'error');

      //@todo sort out the McapiExceptions
      throw new McapiException('', $e->getMessage());
    }

    $this->validated = TRUE;
    //we're actually supposed only to return violations,
    //but the Exceptions also have the same getMessage method
    return array_merge($this->violations);
  }

  /**
   * TEMP
   * Validate the typedData but outside of the form handler
   * @see EntityFormDisplay::validateFormValues
   */
  private function validateTypedData() {
    $violations = [];
    foreach ($this as $field_name => $items) {
      foreach ($items->validate() as $violation) {echo 'Transaction::validateTypedData-'.$field_name;
        $violations[] = $violation;
      }
    }
    $this->typedDataValidated = TRUE;//temp flag
    return $violations;
  }


  /**
   * {@inheritdoc}
   */
  public static function preCreate(EntityStorageInterface $storage, array &$values) {
    $values += [
      'serial' => 0,
      'type' => 'default',
      'description' => '',
      'created' => REQUEST_TIME,
      'creator' => \Drupal::currentUser()->id(),//uid of 0 means drush must have created it
      'parent' => 0,
    ];
  }

  /**
   * save the child transactions, which refer to the saved parent
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
  public function transition($transition, array $values) {
    $context = [
      'values' => $values,
      'old_state' => $this->get('state')->value,
      'transition' => $transition,
    ];

    $renderable = (array)$transition->execute($this, $context)
    or $renderable = 'transition returned nothing renderable';

    //notify other modules, especially rules.
    $renderable += \Drupal::moduleHandler()->invokeAll(
      'mcapi_transition',
      [$this, $transition, $context]
    );

    $event = new TransactionSaveEvents(clone($this), $context);
    //@todo, how to the event handlers return anything,
    //namely more $renderable items?
    \Drupal::service('event_dispatcher')->dispatch(
      McapiEvents::TRANSITION,
      $event
    );

    $this->save();
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

    $fields['description'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Description'))
      ->setDescription(t('A one line description of what was exchanged.'))
      ->setRequired(TRUE)
      ->setSettings(array('default_value' => '', 'max_length' => MCAPI_MAX_DESCRIPTION))
      ->setDisplayConfigurable('form', TRUE);

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
      ->setRequired(TRUE)
      ->setDisplayConfigurable('form', TRUE);

    $fields['payee'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Payee'))
      ->setDescription(t('Id of the receiving wallet'))
      ->setSetting('target_type', 'mcapi_wallet')
      ->setReadOnly(TRUE)
      ->setRequired(TRUE)
      ->setDisplayConfigurable('form', TRUE);

    $fields['creator'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Creator'))
      ->setDescription(t('The user who created the transaction'))
      ->setSetting('target_type', 'user')
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
      ->setDisplayConfigurable('form', TRUE);

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setDescription(t('The time that the transaction was last saved.'))
      ->setRevisionable(FALSE)
      ->setTranslatable(FALSE);

    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  function flatten() {
    $clone = clone($this);
    $flatarray = [$clone];
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
