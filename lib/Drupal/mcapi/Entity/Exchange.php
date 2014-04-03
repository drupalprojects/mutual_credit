<?php

/**
 * @file
 * Contains \Drupal\mcapi\Entity\Exchange.
 */

namespace Drupal\mcapi\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityStorageControllerInterface;
use Drupal\Core\Field\FieldDefinition;
//use Drupal\mcapi\CurrencyInterface;
//use Drupal\Core\Entity\Annotation\EntityType;
//use Drupal\Core\Annotation\Translation;
//use Drupal\mcapi\Plugin\Field\FieldType\Worth;

/**
 * Defines the Exchange entity.
 *
 * @EntityType(
 *   id = "mcapi_exchange",
 *   label = @Translation("Exchange"),
 *   module = "mcapi",
 *   controllers = {
 *     "storage" = "Drupal\mcapi\ExchangeStorageController",
 *     "view_builder" = "Drupal\mcapi\ExchangeViewBuilder",
 *     "access" = "Drupal\mcapi\ExchangeAccessController",
 *     "form" = {
 *       "add" = "Drupal\mcapi\Form\ExchangeForm",
 *       "edit" = "Drupal\mcapi\Form\ExchangeForm",
 *       "delete" = "Drupal\mcapi\Form\ExchangeDeleteConfirm",
 *       "activate" = "Drupal\mcapi\Form\ExchangeOpenConfirm",
 *       "deactivate" = "Drupal\mcapi\Form\ExchangeCloseConfirm",
 *     },
 *     "list" = "Drupal\mcapi\ExchangeListController",
 *   },
 *   admin_permission = "configure mcapi",
 *   fieldable = TRUE,
 *   translatable = FALSE,
 *   base_table = "mcapi_exchanges",
 *   route_base_path = "admin/accounting/exchanges",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "name",
 *     "uuid" = "uuid",
 *     "weight" = "weight",
 *     "status" = "status"
 *   },
 *   links = {
 *     "canonical" = "mcapi.exchange.view",
 *     "edit-form" = "mcapi.exchange.edit",
 *     "admin-form" = "mcapi.admin_exchange_list"
 *   }
 * )
 */
class Exchange extends ContentEntityBase {

  /*
   * get the number of users in this exchange
   */
  function members() {
    //@todo
    //entity_load_by_properties seems expensive and I don't know how to make it work
    //return entity_load_multiple_by_properties('user', array('field_exchanges' => $this->id()));

    //this more direct solution belongs really in the storage controller
    //@todo how the hell does countQuery work?
    return count(db_select("user__field_exchanges", 'e')
      ->fields('e', array('entity_id'))
      ->condition('field_exchanges_target_id', $this->id())
      ->execute()->fetchCol());
  }

  /*
   * get the number of transactions in this exchange's history
   */
   //better load the transaction storage controller for this.
  function transactions($period) {
    //@todo is it worth making a new more efficient function in the storage controller for this?
    $conditions = array('exchange' => $this->get('id')->value, $since = strtotime($period));
    $serials = \Drupal::EntityManager()->getStorageController('mcapi_transaction')->filter($conditions);
    return count(array_unique($serials));
  }

  //ensure the manager of the exchange is actually a member.
  public function postSave(EntityStorageControllerInterface $storage_controller, $update = TRUE) {
    $exchange_manager = user_load($this->get('uid')->value);
    //@todo GORDON I can't see how to do this
    return;
    $exchange_manager->get('field_exchanges')->insert($this->id());
    $exchange_manager->save();
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions($entity_type) {
    $properties['id'] = array(
      'type' => 'integer_field',
    	'label' => t('Exchange ID'),
      'description' => t('The unique exchange ID'),
      'readonly' => TRUE,
      'required' => TRUE
    );
    $properties['uuid'] = array(
      'label' => t('UUID'),
      'description' => t('The exchange UUID.'),
      'type' => 'uuid_field',
      'read-only' => TRUE,
      'required' => TRUE
    );
    $properties['name'] = array(
      'label' => t('Full name'),
      'description' => t('The full name of the exchange.'),
      'type' => 'string_field',
      'required' => TRUE,
      'property_constraints' => array(
        'value' => array('Length' => array('max' => 64)),
      ),
      'translatable' => FALSE,
    );
    $properties['uid'] = array(
      'label' => t('Manager of the exchange'),
      //'description' => t('The one user responsible for administration'),
      'type' => 'entity_reference_field',
      'settings' => array(
        'target_type' => 'user',
        'default_value' => 0,
      ),
      'required' => TRUE,
    );
    $properties['active'] = array(
      'label' => t('Active'),
      'description' => t('TRUE if the exchange is current and working.'),
      'type' => 'boolean_field',
      'settings' => array(
        'default_value' => TRUE,
      ),
    );
    $properties['open'] = array(
      'label' => t('Open'),
      'description' => t('TRUE if the exchange can trade with other exchanges'),
      'type' => 'boolean_field',
      'settings' => array(
        'default_value' => TRUE,
      ),
    );
    $properties['visibility'] = array(
      'label' => t('Visibility'),
      'description' => t('Visibility of impersonal data in the exchange'),
      'type' => 'string_field',
      'settings' => array(
        'default_value' => 'restricted',
      ),
      'property_constraints' => array(
        'value' => array('Length' => array('max' => 16)),
      ),
      'required' => TRUE,
    );
    return $properties;
  }
  /**
   * Check if an entity is a member of this exchange
   * @param ContentEntityInterface $entity
   * @return Boolean
   *   TRUE if the entity is a member
   */
  public function member(ContentEntityInterface $entity) {
    if ($entity->entityType() == 'mcapi_wallet') {
      if ($entity->owner) {
        $entity = $entity->owner;
      }
      else return FALSE;
    }
    module_load_include('inc', 'mcapi');
    $fieldname = get_exchange_entity_fieldnames($entity->entityType());
    //@todo I can't work out how to do this with the entity_reference property api
    //so just using the database for now.
    return db_select($entity->entityType() .'__'.$fieldname, 'f')
      ->fields('f', array('entity_id'))
      ->condition($fieldname.'_target_id', $this->id())
      ->condition('entity_id', $entity->id())
      ->execute()->fetchField();
  }
  /**
   * Check whether the passed user is the exchange manager
   * @param AccountInterface $account
   * @return boolean
   */
  public function isManager(AccountInterface $account = NULL) {
    if (is_null($account)) {
      $account = \Drupal::currentUser();
    }
    return $account->id() == $this->get('uid')->value;
  }


  public function visibility_options($val = NULL) {
    $options = array(
    	'private' => t('Private'),
      'restricted' => t('Restricted'),
      'public' => t('Public')
    );
    if ($val) return $options[$val];
    return $options;
  }

  /**
   * check if an exchange, and all the transactions in it can be deleted, which means all of
   * the exchange is already disabled (closed)
   * the delete mode allows its transactions to be deleted.
   *
   * @param EntityInterface $exchange
   * @return Boolean
   */
  function deletable(EntityInterface $exchange) {
    if ($exchange->get('active')->value) return FALSE;
    if (\Drupal::config('mcapi.misc')->get('indelible')) {
      return user_access('manage mcapi');
    }
    return TRUE;
  }

  /**
   * check if an exchange can be deactivated, which means
   * it is not the only active exchange
   *
   * @param EntityInterface $exchange
   * @return Boolean
   */
  function deactivatable($exchange) {
    static $active_exchange_ids = array();
    if (!$active_exchange_ids) {
      //get the names of all the open exchanges
      foreach (entity_load_multiple('mcapi_exchange') as $entity) {
        if ($exchange->get('open')->value) {
          $active_exchange_ids[] = $entity->id();
        }
      }
    }
    if (count($active_exchange_ids) > 1)return TRUE;
    return FALSE;
  }

  function intertrading_wallet() {
    $wallets = entity_load_multiple_by_properties('mcapi_wallet', array('name' => '_intertrade', 'pid' => $this->id()));
    if (wallets)return current($wallets);
    throw new Exception('no _intertrade wallet for Exchange '.$this->id());
  }
}

