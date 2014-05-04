<?php

/**
 * @file
 * Contains \Drupal\mcapi\Entity\Exchange.
 * //TODO implement the EntityOwnerInterface https://drupal.org/node/2188299
 */

namespace Drupal\mcapi\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Field\FieldDefinition;
use Drupal\user\EntityOwnerInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\user\UserInterface;
//use Drupal\mcapi\CurrencyInterface;
//use Drupal\Core\Entity\Annotation\EntityType;
//use Drupal\Core\Annotation\Translation;
//use Drupal\mcapi\Plugin\Field\FieldType\Worth;

define('EXCHANGE_VISIBILITY_PRIVATE', 0);
define('EXCHANGE_VISIBILITY_RESTRICTED', 1);
define('EXCHANGE_VISIBILITY_TRANSPARENT', 2);

/**
 * Defines the Exchange entity.
 *
 * @EntityType(
 *   id = "mcapi_exchange",
 *   label = @Translation("Exchange"),
 *   module = "mcapi",
 *   controllers = {
 *     "storage" = "Drupal\mcapi\ExchangeStorage",
 *     "view_builder" = "Drupal\mcapi\ExchangeViewBuilder",
 *     "access" = "Drupal\mcapi\ExchangeAccessController",
 *     "form" = {
 *       "add" = "Drupal\mcapi\Form\ExchangeForm",
 *       "edit" = "Drupal\mcapi\Form\ExchangeForm",
 *       "delete" = "Drupal\mcapi\Form\ExchangeDeleteConfirm",
 *       "activate" = "Drupal\mcapi\Form\ExchangeOpenConfirm",
 *       "deactivate" = "Drupal\mcapi\Form\ExchangeCloseConfirm",
 *     },
 *     "list" = "Drupal\mcapi\ExchangeListBuilder",
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
class Exchange extends ContentEntityBase implements EntityOwnerInterface {

  /**
   * {@inheritdoc}
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

  /**
   * {@inheritdoc}
   */
  function transactions($period) {
    //@todo is it worth making a new more efficient function in the storage controller for this?
    $conditions = array('exchange' => $this->get('id')->value, $since = strtotime($period));
    $serials = \Drupal::EntityManager()->getStorage('mcapi_transaction')->filter($conditions);
    return count(array_unique($serials));
  }



  /**
   * {@inheritdoc}
   */
  public function postSave(EntityStorageInterface $storage_controller, $update = TRUE) {
    //ensure the manager of the exchange is actually a member.
    $exchange_manager = user_load($this->get('uid')->value);
    //TODO GORDON I can't see how to do this
    return;
    $exchange_manager->get('field_exchanges')->insert($this->id());
    $exchange_manager->save();
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {

    $fields['id'] = FieldDefinition::create('integer')
      ->setLabel(t('Exchange ID'))
      ->setDescription(t('The unique exchange ID.'))
      ->setReadOnly(TRUE)
      ->setSetting('unsigned', TRUE);

    $fields['uuid'] = FieldDefinition::create('uuid')
      ->setLabel(t('UUID'))
      ->setDescription(t('The exchange UUID.'))
      ->setReadOnly(TRUE);

    $fields['name'] = FieldDefinition::create('string')
      ->setLabel(t('Full name'))
      ->setDescription(t('The full name of the exchange.'))
      ->setRequired(TRUE)
      ->setSettings(array('default_value' => '', 'max_length' => 64))
      ->setDisplayOptions(
        'view',
        array('label' => 'hidden', 'type' => 'string', 'weight' => -5)
      );

    $fields['uid'] = FieldDefinition::create('entity_reference')
      ->setLabel(t('Manager of the exchange'))
      ->setSetting('target_type', 'user')
      ->setRequired(TRUE);

    $fields['active'] = FieldDefinition::create('boolean')
      ->setLabel(t('Active'))
      ->setDescription(t('TRUE if the exchange is current and working.'))
      ->setSettings(array('default_value' => TRUE));

    $fields['open'] = FieldDefinition::create('boolean')
      ->setLabel(t('Open'))
      ->setDescription(t('TRUE if the exchange can trade with other exchanges'))
      ->setSettings(array('default_value' => TRUE));

    $fields['visibility'] = FieldDefinition::create('integer')
      ->setLabel(t('Visibility'))
      ->setDescription(t('Visibility of impersonal data in the exchange'))
      ->setRequired(TRUE)
      ->setSetting('default_value', EXCHANGE_VISIBILITY_RESTRICTED);

    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public function member(ContentEntityInterface $entity) {
    $entity_type = $entity->getEntityTypeId();
    if ($entity_type == 'mcapi_wallet') {
      if ($entity->owner) {
        $entity = $entity->owner;
      }
      else return FALSE;
    }
    module_load_include('inc', 'mcapi');
    $fieldname = get_exchange_entity_fieldnames($entity_type);
    //@todo I can't work out how to do this with the entity_reference property api
    //so just using the database for now.
    return db_select($entity_type .'__'.$fieldname, 'f')
      ->fields('f', array('entity_id'))
      ->condition($fieldname.'_target_id', $this->id())
      ->condition('entity_id', $entity->id())
      ->execute()->fetchField();
  }

  /**
   * {@inheritdoc}
   */
  public function isManager(AccountInterface $account = NULL) {
    if (is_null($account)) {
      $account = \Drupal::currentUser();
    }
    return $account->id() == $this->get('uid')->value;
  }

  /**
   * {@inheritdoc}
   */
  function deletable(EntityInterface $exchange) {
    if ($exchange->get('active')->value) return FALSE;
    if (\Drupal::config('mcapi.misc')->get('indelible')) {
      return user_access('manage mcapi');
    }
    return TRUE;
  }

  /**
   * {@inheritdoc}
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

  /**
   * {@inheritdoc}
   */
  function intertrading_wallet() {
    $wallets = entity_load_multiple_by_properties('mcapi_wallet', array('name' => '_intertrade', 'pid' => $this->id()));
    if (wallets)return current($wallets);
    throw new Exception('no _intertrade wallet for Exchange '.$this->id());
  }


  //utility function not in the interface
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
   * {@inheritdoc}
   */
  public function getOwner() {
    return $this->get('uid')->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function getOwnerId() {
    return $this->get('uid')->target_id;
  }

  /**
   * {@inheritdoc}
   */
  public function setOwnerId($uid) {
    $this->set('uid', $uid);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setOwner(UserInterface $account) {
    $this->set('uid', $account->id());
    return $this;
  }

}

