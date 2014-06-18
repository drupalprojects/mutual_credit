<?php

/**
 * @file
 * Contains \Drupal\mcapi\Entity\Transaction.
 * @todo on all entity types sort out the linkTemplates https://drupal.org/node/2221879
 */

namespace Drupal\mcapi\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Field\FieldDefinition;
use Drupal\mcapi\McapiTransactionException;
use Drupal\mcapi\Plugin\Field\McapiTransactionWorthException;
use Drupal\Core\Entity\EntityTypeInterface;

/**
 * Defines the Transaction entity.
 * Transactions are grouped and handled by serial number
 * but the unique database key is the xid. Still not sure how to use the entity_keys
 * to reflect this.
 *
 * @ContentEntityType(
 *   id = "mcapi_transaction",
 *   label = @Translation("Transaction"),
 *   controllers = {
 *     "storage" = "Drupal\mcapi\Storage\TransactionStorage",
 *     "view_builder" = "Drupal\mcapi\ViewBuilder\TransactionViewBuilder",
 *     "access" = "Drupal\mcapi\Access\TransactionAccessController",
 *     "form" = {
 *       "transition" = "Drupal\mcapi\Form\TransitionForm",
 *       "admin" = "Drupal\mcapi\Form\TransactionForm",
 *       "delete" = "Drupal\mcapi\Form\TransactionDeleteConfirm",
 *     },
 *   },
 *   admin_permission = "configure mcapi",
 *   base_table = "mcapi_transactions",
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
  public function label($langcode = NULL) {
    return t("Transaction #@serial", array('@serial' => $this->get('serial')->value));
  }

  /**
   * {@inheritdoc}
   * @todo update this in line with https://drupal.org/node/2221879
   * actually can this be removed? Is it deprecated in favour of this->url
   */
  public function uri($rel = 'canonical') {
    debug('This function is still used!!');
    $renderable = parent::uri($rel);
    $renderable['path'] = 'transaction/'. $this->get('serial')->value;
    die('uri');
    return $renderable;
  }

  protected function urlRouteParameters($rel) {
    return array('mcapi_transaction' => $this->get('serial')->value);
  }

  /**
   *
   * @throws mcapiTransactionException
   * @return Ambigous <void, multitype:unknown >
   */
  function validate() {
    //all this does is check the field values against the field definitions.
    //Don't know how to include the worth field in that
    $violations = array();
    $warnings = array();
    if ($this->isNew()) {
      $type = $this->type->getValue(TRUE);
      $this->state->setValue($type[0]['entity']->start_state);
      $violations += $this->validateNew();
      //for transactions created in code, this is the best place to throw
      if ($warnings) {
        $child_errors = \Drupal::config('mcapi.misc')->get('child_errors');
        if (!$child_errors['allow']) {
          throw new mcapiTransactionException(key($warnings), implode(' ', $warnings));
        }
      }
    }
    //validate is being broken by the timedate field in alpha11
    //$violations = parent::validate();
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
      $violation_messages[] = $violation[1];//TODO this is temp really we want (string)$violation
    }
    //TODO after alpha11 how can the checkintegrity function return violations?
    //then this function needs to return violations
    $violation_messages += $this->checkIntegrity();

    return $violation_messages; //temp need to tidy this up
  }

  /**
   * @see \Drupal\mcapi\Entity\TransactionInterface::validateNew()
   */
  public function validateNew() {
    //any thrown messages here are caught and shown to the user as a list of problems with the transaction.
    $warnings = array();
    $violations = array();
    $fatalities = array();
    if (!$this->validated) {
      $this->mcapiExceptions = array();
      $this->moduleHandler = \Drupal::moduleHandler();

      //we could do this on a loop with many validation functions e.g. $this->$functionname()
      $violations += $this->checkUniqueUuid();

      //on the parent transaction only, validate it and then pass it round to get child candidates
      if ($this->parent->value == 0) {
        $violations += $this->validateMakeChildren();//includes intertrading
        if (empty($violations)) {
          //note that this validation cannot change the original transaction because a clone of it is passed
          $violations += $this->moduleHandler->invokeAll('mcapi_transaction_validate', array(mcapi_transaction_flatten($this)));

          //validate the child candidates
          foreach ($this->children as $child) {
            //ensure the child transactions aren't mistaken for parents during validation
            $child->parent->value = -1;
            $child->violations = $child->validateNew();
          }
          //process the errors in the children.
          $child_errors = \Drupal::config('mcapi.misc')->get('child_errors');
          foreach ($this->children as $key => $child) {
            $child->validate();
            if ($child->mcapiExceptions) {
              foreach ($child->mcapiExceptions as $warning) {
                $warnings[] = $warning;
              }
              $replacements = array(
                  '!messages' => implode(' ', $warnings),
                  //'!dump' => print_r($this, TRUE)//TODO for some reason print_r is printing not returning
              );
              if ($child_errors['log']){
                \Drupal::logger('mcapi')->error(
                  t("transaction failed validation with the following messages: !messages", array('!messages' => $message))
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
        }
        //handle multiple stored exceptions, joining them together in one error message
        foreach ($violations as $fieldname => $violation) {
          $fatalities[$fieldname] = $violation;
        }
      }
    }
    $this->validated = TRUE;
    return $fatalities;
  }


  /**
   * needed for validation of non-new transactions
   * last thing before writing to the database.
   * transactions may be new or updated, and may not have been through the formAPI
   * @return an array of violations
   */
  function checkIntegrity() {
    //we need to be throwing violations here
    return $this->checkDifferentWallets();
    //is it worth checking whether all the entity properties and fields are actually fieldItemLists?
    //check that the description string isn't too long?
    //check that the payer and payee wallets are in the same exchange?
  }


  /**
   * @see \Drupal\mcapi\Entity\TransactionInterface::preCreate($storage_controller)
   *
   * @param EntityStorageInterface $storage
   * @param array $values
   */
  public static function preCreate(EntityStorageInterface $storage, array &$values) {
    $values += array(
      'parent' => 0,
      //'children' => array(),
      'serial' => 0,
      'type' => 'default',
      'description' => '',
      'created' => REQUEST_TIME,
      'creator' => \Drupal::currentUser()->id()//uid of 0 means drush must have created it
    );
  }

  /**
   * In the case of transactions made by code, not through the form interface,
   * validation is done as part of saving.
   * This is because there is no entity-level validation in drupal.
   *
   * @throws McapiTransactionException
   *
   * @see \Drupal\mcapi\Entity\TransactionInterface::preSave($storage_controller)
   *
   */
  public function preSave(EntityStorageInterface $storage_controller) {
    $this->validate();
  }

  /**
   * save the child transactions, which refer to the saved parent
   */
  public function postSave(EntityStorageInterface $storage_controller, $update = TRUE) {
    //TODO clear the entity cache of all wallets involved in this transaction and its children
    //because the wallet entity cache contains the balance limits and the summary stats
    $wids = array($this->payer->value, $this->payee->value);
    drupal_set_message ('todo: clear wallet cache for wallet ids '.implode(' & ', $wids));

    //this is especially for invoking rules
    if(property_exists($this, 'context')) {
      //context is an array with keys: op_plugin_id, old_state, config. see TransitionForm::Submit
      //TODO populate the $context when
      $this->moduleHandler->invokeAll('mcapi_transaction_operated', array($this, $this->context));
    }
  }

  public function transition($transition_name, array $values) {

    $context = array(
      'values' => $values,
      'old_state' => $this->get('state')->value,
      'config' => $this->configuration,
    );

    $config = \Drupal::config('mcapi.transition.'.$this->transition);
    //any problems need to be thrown
    $renderable = transaction_transitions($transition_name)->execute($this, $context)
      or $renderable = 'transition returned nothing renderable';

    //notify other modules, especially rules.
    //should we send a clone of the transaction?
    //Should we rely on hook_mcapi_transaction_update and  for this?
    //perhaps not because we need the context...

    $renderable += \Drupal::moduleHandler()->invokeAll('mcapi_transaction_operated', array($this, $context));
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
      ->setSettings(array('default_value' => '', 'max_length' => 255))
      ->setDisplayOptions(
        'view',
        array('label' => 'hidden', 'type' => 'string', 'weight' => -5)
      );

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

  private function checkDifferentWallets() {
    //check that the payer and payee are not the same wallet
    $violations = array();
    //TODO return a violation
    if ($this->payer->value == $this->payee->value) {
      $violations['payee'] = t('Wallet @wid is attempting to pay itself.', array('@wid' => $this->payer->value));
    }
    return $violations;
  }

  function checkUniqueUuid() {
    $violations = array();
    //check that the uuid doesn't already exist. i.e. that we are not resubmitting the same transactions
    //TODO return a violation
    $properties = array('uuid' => $this->get('uuid')->value);
    if (entity_load_multiple_by_properties('mcapi_transaction', $properties)) {
      $violations['uuid'] = t('Transaction has already been inserted: @uuid', array('@uuid' => $this->uuid->value));
    }
    return $violations;
  }

  //this runs only on the top-level transaction
  private function validateMakeChildren() {
    //prevent the main transaction being altered as we pass it round
    //the main transaction was already altered in the preValidate hook.
    $clone = clone($this);
    $violations = array();
    $this->children = array();//ONLY parent transactions have this property
    //previous the children's parent was set to temp, but is that necessary?
    $this->children = $this->moduleHandler->invokeAll('mcapi_transaction_children', array($clone));
    //TODO how does the addition of children here affect intertrading? badly I suspect
    $this->moduleHandler->alter('mcapi_transaction_children', $this->children, $clone);
    //how the children have been created but before they are validated, we do the intertrading

    $this->payer_currencies_available = array_keys($this->payer->entity->currencies_available());
    $this->payee_currencies_available = array_keys($this->payer->entity->currencies_available());

    foreach ($this->get('worth')->getValue() as $item) {
      $this->curr_ids_required[] = $item['curr_id'];
    }
    //to determine the given currencies are shared between both users
    $this->common_curr_ids = array_intersect($this->payer_currencies_available, $this->payee_currencies_available);

    if (array_diff($this->curr_ids_required, $this->common_curr_ids)) {
      module_load_include('inc', 'mcapi');
      //GENERATE AN INTERTRADING CHILD TRANSACTION
      //this means there is at least one currency in the transaction not common to both wallets' exchanges
      //so the only way to do this transaction is with intertrading.
      //That means we create a transaction between each wallet and its exchange's _intertrading wallet
      //In effect the current transaction is altered and a child added

      //this creates the following temp transaction properties:
      //$this->source_exchange  an exchange ID whose intertrading wallet to use
      //$this->source_participant //the property name of the participant in the source exchange; either payer or payee
      //$this->dest_participant) //the property name of the participant in the source exchange; either payee or payer
      $violations += intertrading_transaction_validate($this);
      if ($violations) return $violations;
      //we might want to abstract this into a plugin or something to allow
      //different mechanisms for taking a commission. We would need much
      //better separation between validation and generation
      //This function adds new properties to the transaction
      //$this->dest_exchange the id of the destination exchange
      //$this->dest_worths the id of the destination exchange
      $warnings = intertrading_new_worths($this, $this->{$this->dest_participant}->entity);
      if ($warnings) {
        return $warnings;
      }

      $new_transaction = clone($this);//since it hasn't been save there is no xid or serial number yet
      $new_transaction->uuid->setValue(\Drupal::service('uuid')->generate());
      $this->set($this->dest_participant, $this->source_exchange->intertrading_wallet()->id());
      $new_transaction->set($this->source_participant, $this->dest_exchange->intertrading_wallet()->id());
      $new_transaction->set('worth', $this->dest_worths);
      $this->children[] = $new_transaction;
    }
    else {
      return $violations;
      echo ('<br />Normal transaction - no intertrading...');
      echo '<br />traders: '.$this->get('payer')->value.', '.$this->get('payee')->value;
      echo '<br />payer_currencies_available '; print_r($this->payer_currencies_available);
      echo '<br />payee_currencies_available '; print_r($this->payee_currencies_available);
      echo '<br />required currs '; print_r($this->curr_ids_required);
      echo '<br />common_curr_ids '; print_r($this->common_curr_ids);
    }
    return $violations;
  }


}
