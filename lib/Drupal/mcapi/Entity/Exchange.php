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
 *       "open" = "Drupal\mcapi\Form\ExchangeOpenConfirm",
 *       "close" = "Drupal\mcapi\Form\ExchangeCloseConfirm",
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

  //@todo
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

    $properties['id'] = FieldDefinition::create('integer')
    ->setLabel('Exchange ID')
    ->setDescription('the unique exchange ID')
    ->setReadOnly(TRUE)
    ->setRequired(TRUE);
    $properties['uuid'] = FieldDefinition::create('uuid')
    ->setLabel('UUID')
    ->setDescription('The transaction UUID.')
    ->setReadOnly(TRUE)
    ->setRequired(TRUE);
    $properties['name'] = FieldDefinition::create('string')
    ->setLabel('Full name')
    ->setDescription('The full name of the exchange')
    ->setPropertyConstraints('value', array('Length' => array('max' => 64)))
    ->setRequired(TRUE);
    $properties['uid'] = FieldDefinition::create('entity_reference')
    ->setLabel('Manager of the exchange')
    ->setDescription('The one user responsible for administration')
    ->setSettings(array('target_type' => 'user'))
    ->setRequired(TRUE);
    $properties['open'] = FieldDefinition::create('boolean')
    ->setLabel('Open')
    ->setDescription('TRUE if the exchange is open for trading')
    ->setSetting('default_value', TRUE);
    $properties['visibility'] = FieldDefinition::create('boolean')
    ->setLabel('Visibility')
    ->setDescription('Visibility of impersonal data in the exchange')
    ->setSetting('default_value', 'restricted');
    $properties['langcode'] = FieldDefinition::create('language')
    ->setLabel(t('Language code'))
    ->setDescription(t('The first language of the exchange'));
    //plus don't forget there is an entityreference field api field and instance called exchange_currencies

    return $properties;
  }
  /**
   * Check if an entity is a member of this exchange
   * @param ContentEntityInterface $entity
   * @return Boolean
   *   TRUE if the entity is a member
   */
  public function member(ContentEntityInterface $entity) {
    if ($entity->entityType() == 'wallet') {
      if ($wallet->owner) {
        $entity = $wallet->owner;
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

  public function visibility_options($val = NULL) {
    $options = array(
    	'private' => t('Private'),
      'restricted' => t('Restricted'),
      'public' => t('Public')
    );
    if ($val) return $options[$val];
    return $options;
  }

}

