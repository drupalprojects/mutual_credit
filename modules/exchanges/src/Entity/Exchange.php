<?php

/**
 * @file
 * Contains \Drupal\mcapi_exchanges\Entity\Exchange.
 */

namespace Drupal\mcapi_exchanges\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\user\UserInterface;
use Drupal\user\EntityOwnerInterface;
use Drupal\mcapi\Entity\Wallet;
use Drupal\mcapi_exchanges\ExchangeInterface;
use Drupal\mcapi\Mcapi;


/**
 * Defines the Exchange entity.
 *
 * @ContentEntityType(
 *   id = "mcapi_exchange",
 *   label = @Translation("Exchange"),
 *   handlers = {
 *     "storage" = "Drupal\mcapi_exchanges\ExchangeStorage",
 *     "view_builder" = "Drupal\mcapi_exchanges\ExchangeViewBuilder",
 *     "access" = "Drupal\mcapi_exchanges\ExchangeAccessControlHandler",
 *     "list_builder" = "Drupal\mcapi_exchanges\ExchangeListBuilder",
 *     "form" = {
 *       "add" = "Drupal\mcapi_exchanges\Form\ExchangeWizard",
 *       "edit" = "Drupal\mcapi_exchanges\Form\ExchangeForm",
 *       "delete" = "Drupal\mcapi_exchanges\Form\ExchangeDeleteConfirm",
 *       "join" = "Drupal\mcapi_exchanges\Form\Join",
 *       "masspay" = "Drupal\mcapi_exchanges\Form\MassPay",
 *       "enable" = "Drupal\mcapi_exchanges\Form\ExchangeEnableConfirm",
 *       "disable" = "Drupal\mcapi_exchanges\Form\ExchangeDisableConfirm",
 *     },
 *     "views_data" = "Drupal\mcapi_exchanges\ExchangeViewsData",
 *     "route_provider" = {
 *       "html" = "\Drupal\Core\Entity\Routing\DefaultHtmlRouteProvider",
 *     },
 *   },
 *   admin_permission = "configure mcapi",
 *   translatable = FALSE,
 *   base_table = "mcapi_exchange",
 *   field_ui_base_route = "mcapi.admin_exchange_list",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "name",
 *     "uuid" = "uuid",
 *     "weight" = "weight",
 *     "status" = "status"
 *   },
 *   links = {
 *     "canonical" = "/exchange/{mcapi_exchange}",
 *     "edit-form" = "/exchange/{mcapi_exchange}/edit",
 *     "join-form" = "/exchange/{mcapi_exchange}/join",
 *     "delete-confirm" = "/exchange/{mcapi_exchange}/delete",
 *     "enable-confirm" = "/exchange/{mcapi_exchange}/enable",
 *     "disable-confirm" = "/exchange/{mcapi_exchange}/disable"
 *   }
 * )
 */
class Exchange extends ContentEntityBase implements EntityOwnerInterface, ExchangeInterface{


  const VISIBILITY_PRIVATE =  0;
  const VISIBILITY_RESTRICTED = 1;
  const VISIBILITY_TRANSPARENT = 2;

  /**
   * {@inheritdoc}
   */
  public static function preCreate(EntityStorageInterface $storage_controller, array &$values) {
    $values += [
      //'name' => t("%name's exchange", ['%name' => $account->label()]),
      'uid' => \Drupal::currentUser()->id() ? : 1,//drush is user 0
      'status' => TRUE,
      'open' => TRUE,
      'visibility' => TRUE,
    ];
  }

  /**
   * Create the _intertrading wallet and ensure the manager user is in the exchange
   * @param EntityStorageInterface $storage
   * @param boolean $update
   *   whether the wallet already existed
   */
  public function postSave(EntityStorageInterface $storage, $update = TRUE) {
    //@todo add the manager user to the exchange if it is not a member
    //$exchange_manager = User::load($this->get('uid')->value);

    //create a new intertrading wallet for new exchanges
    if (!$update) {
      $props = [
        'holder' => $this,
        'payways' => Wallet::PAYWAY_AUTO,
      ];
      Wallet::create($props)->save();
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields['id'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Exchange ID'))
      ->setDescription(t('The unique exchange ID.'))
      ->setReadOnly(TRUE)
      ->setSetting('unsigned', TRUE);

    $fields['uuid'] = BaseFieldDefinition::create('uuid')
      ->setLabel(t('UUID'))
      ->setDescription(t('The exchange UUID.'))
      ->setReadOnly(TRUE);

    $fields['langcode'] = BaseFieldDefinition::create('language')
      ->setLabel(t('Language code'))
      ->setDescription(t('Language used.'))
      ->setTranslatable(TRUE);

    $fields['name'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Full name'))
      ->setDescription(t('The full name of the exchange.'))
      ->setRequired(TRUE)
      ->setSettings(['default_value' => '', 'max_length' => 64])
      ->setDisplayOptions(
        'view',
        ['label' => 'hidden', 'type' => 'string', 'weight' => -5]
      )
      ->addConstraint('UniqueField');

    $fields['code'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Machine name'))
      ->setSettings(['default_value' => '', 'max_length' => 32])
      ->setReadOnly(TRUE);

    $fields['body'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Home page content'))
      ->setDescription(t('Welcome, about us, how to join etc.'))
      ->setRequired(TRUE)
      ->setTranslatable(TRUE)
      ->setSettings(['default_value' => ''])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['uid'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Manager of the exchange'))
      ->setSetting('target_type', 'user')
      ->setRequired(TRUE);

    $fields['status'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Active'))
      ->setDescription(t('TRUE if the exchange is current and working.'))
      ->setSettings(['default_value' => TRUE]);

    $fields['open'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Open'))
      ->setDescription(t('TRUE if the exchange can trade with other exchanges'))
      ->setSettings(['default_value' => TRUE]);

    $fields['visibility'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Visibility'))
      ->setDescription(t('Visibility of impersonal data in the exchange'))
      ->setRequired(TRUE)
      ->setSetting('default_value', SELF::VISIBILITY_RESTRICTED);

    $fields['mail'] = BaseFieldDefinition::create('email')
      ->setLabel(t('Email'))
      ->setDescription(t('The official contact address'))
      ->setRequired(TRUE);

    $fields['logo'] = BaseFieldDefinition::create('image')
      ->setLabel(t('Logo'))
      ->setDescription(t('A small image'))
      ->setSettings(['default_value' => TRUE])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE)
      ->setSetting('max_resolution', '400x100')
      ->setSetting('min_resolution', '80x80');

    $fields['created'] = BaseFieldDefinition::create('created')
    ->setLabel(t('Created'))
    ->setDescription(t('The time that the exchange was created.'))
    ->setTranslatable(TRUE)
    ->setDisplayOptions('view', [
      'label' => 'hidden',
      'type' => 'hidden',
    ])
    ->setDisplayConfigurable('view', TRUE);

    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public function intertradingWallet() {
    $wallets = $this->entityTypeManager()
      ->getStorage('mcapi_wallet')
      ->loadByProperties(['payways' => Wallet::PAYWAY_AUTO, 'holder_entity_id' => $this->id()]);
    return reset($wallets);
  }


  /**
   * get one or all of the visibililty types with friendly names
   * @param integer $constant
   * @return mixed
   *   an array of visibility type names, keyed by integer constants or just one name
   */
  public function visibility_options($constant = NULL) {
    $options = [
      SELF::VISIBILITY_PRIVATE => t('Private except to members'),
      SELF::VISIBILITY_RESTRICTED => t('Restricted to members of this site'),
      SELF::VISIBILITY_TRANSPARENT => t('Transparent to the public')
    ];
    if (is_null($constant))return $options;
    return $options[$constant];
  }


  /**
   * {@inheritdoc}
   * @todo remove uid field and change getOwner to be the first person in the lead og_role
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

  /**
   * {@inheritdoc}
   */
  function deletable() {
    if ($this->get('status')->value) {
      $this->reason = t('Exchange must be disabled');
      return FALSE;
    }
    if (count($this->intertradingWallet()->history())) {
      $this->reason = t('Exchange intertrading wallet has transactions');
      return FALSE;
    }
    //if the exchange has wallets, even orphaned wallets, it can't be deleted.
    $wallet_ids = Mcapi::walletsOf($this);
    if (count($wallet_ids)) {
      $this->reason = t('The exchange still owns wallets: @nums', ['@nums' => implode(', ', $wallet_ids)]);
      return FALSE;
    }
    return TRUE;
  }

    /**
   * {@inheritdoc}
   */
  function deactivatable() {
    static $active_exchange_ids = [];
    if (!$active_exchange_ids) {
      //get the names of all the open exchanges
      foreach (Self::loadMultiple() as $entity) {
        if ($entity->get('status')->value) {
          $active_exchange_ids[] = $entity->id();
        }
      }
    }
    if ($this->get('status')->value && count($active_exchange_ids) > 1)return TRUE;
    return FALSE;
  }

  /**
   * reverse lookup to see if a given user is a member of this exchange
   * @todo rewrite this completely
   */
  public function hasMember(AccountInterface $entity) {
    if ($this->multiple) {
      return _og_is_member(
        'mcapi_exchange',
        $this->id(),
        $entity->getEntityType(),
        $entity,
        [OG_STATE_ACTIVE]
      );

      //@todo remove all this
      if ($entity->getEntityTypeId() == 'mcapi_wallet') {
        $entity = $entity->getOwner();
      }
      $id = $entity->id();
      $members = $this->{EXCHANGE_OG_FIELD}->referencedEntities();

      foreach ($members as $account) {
        if ($account->id() == $id) return TRUE;
      }
      return FALSE;
    }
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function memberIds($entity_type) {
    return \Drupal::entityQuery('og_membership')
      ->condition('member_entity_type', $entity_type)
      ->condition('group_entity_type', 'mcapi_exchange')
      ->condition('group_entity_id', $this->id())
      ->execute();
  }

  /**
   *
   * @return integer
   *   the number of transactions, by serial, in the exchange
   */
  function transactionCount(array $conditions = []) {
    $query = \Drupal::entityTypeManager()
      ->getStorage('mcapi_transaction')->getQuery();
    foreach ($conditions as $field => $val) {
       $operator = is_array($val) ? '<>' : '=';
       $query->condition($field, $val, $operator);
    }
    //get all the wallets in this exchange
    $query->condition('involving', Exchanges::walletsInExchange([$this->id()]));

    $serials = $query->execute();
    return count(array_unique($serials));
  }
}



