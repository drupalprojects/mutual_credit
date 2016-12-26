<?php

namespace Drupal\mcapi\Entity;

use Drupal\user\UserInterface;
use Drupal\user\Entity\User;
use Drupal\mcapi\Mcapi;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Field\BaseFieldDefinition;

/**
 * Defines the wallet entity.
 *
 * The metaphor is not perfect because this wallet can hold a negaive balance.
 * A more accurate term, 'account', has another meaning in Drupal.
 *
 * This Entity knows all about the special intertrading wallets though none are
 * installed by default.
 *
 * @note Wallet 'holders' could be any content entity, but the wallet owner is
 * always a user, typically the owner of the holder entity
 *
 * @ContentEntityType(
 *   id = "mcapi_wallet",
 *   label = @Translation("Wallet"),
 *   module = "mcapi",
 *   handlers = {
 *     "view_builder" = "Drupal\mcapi\ViewBuilder\WalletViewBuilder",
 *     "storage" = "Drupal\mcapi\Storage\WalletStorage",
 *     "storage_schema" = "Drupal\mcapi\Storage\WalletStorageSchema",
 *     "access" = "Drupal\mcapi\Access\WalletAccessControlHandler",
 *     "form" = {
 *       "edit" = "Drupal\mcapi\Form\WalletForm",
 *       "create" = "Drupal\mcapi\Form\WalletAddForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *     "views_data" = "Drupal\mcapi\Views\WalletViewsData",
 *     "route_provider" = {
 *       "html" = "Drupal\mcapi\Entity\WalletRouteProvider",
 *     },
 *   },
 *   admin_permission = "configure mcapi",
 *   base_table = "mcapi_wallet",
 *   __label_callback = "Drupal\mcapi\ViewBuilder\WalletViewBuilder::DefaultLabel",
 *   entity_keys = {
 *     "label" = "name",
 *     "id" = "wid",
 *     "uuid" = "uuid"
 *   },
 *   translatable = FALSE,
 *   links = {
 *     "canonical" = "/wallet/{mcapi_wallet}",
 *     "log" = "/wallet/{mcapi_wallet}/log",
 *     "inex" = "/wallet/{mcapi_wallet}/inex",
 *     "delete-form" = "/wallet/{mcapi_wallet}/delete",
 *     "edit-form" = "/wallet/{mcapi_wallet}/edit"
 *   },
 *   field_ui_base_route = "mcapi.admin_wallets"
 * )
 */
class Wallet extends ContentEntityBase implements WalletInterface {

  private $holder;
  private $stats = [];

  /**
   * {@inheritdoc}
   */
  public function getHolder() {
    if (!isset($this->holder)) {
      $this->holder = $this->entityTypeManager()
        ->getStorage($this->holder_entity_type->value)
        ->load($this->holder_entity_id->value);
      // In the impossible event that there is no owner, set.
      if (!$this->holder) {
        throw new \Exception('Holder of wallet ' . $this->id() . ' does not exist: ' . $this->holder_entity_type->value . ' ' . $this->holder_entity_id->value);
      }
    }
    return $this->holder;
  }

  /**
   * {@inheritdoc}
   */
  public function setHolder(ContentEntityInterface $entity) {
    $this->holder = $entity;
    $this->holder_entity_id->value = $entity->id();
    $this->holder_entity_type->value = $entity->getEntityTypeId();
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public static function preCreate(EntityStorageInterface $storage_controller, array &$values) {
    if (!isset($values['holder'])) {
      // This is an error or a temp entityType.
      // @see _field_create_entity_from_ids
      // set a temp holder.
      $values['holder'] = User::load(1);
    }
    $values['holder_entity_type'] = $values['holder']->getEntityTypeId();
    $values['holder_entity_id'] = $values['holder']->id();
  }

  /**
   * {@inheritdoc}
   */
  public function presave(EntityStorageInterface $storage) {
    // Autoname the wallet if it is its holders only wallet.
    if ($name = $this->autoName()) {
      $this->name->value = $name;
    }
    // If the holding entity isn't a user, then we need to add the user owner to
    // the list of payees and payers.
    elseif ($this->holder_entity_type->value != 'user') {
      $bursers = $this->bursers->referencedEntities();
      $bursers[] = $this->getOwner();
      $bursers = array_unique($bursers);
      $this->bursers->setValue($bursers);
      drupal_set_message(t("Allowing owner of @type to be a payee for the @type's wallet", ['@type' => $this->holder_entity_type->value]));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function autoName() {
    if (Mcapi::maxWalletsOfBundle($this->getHolder()->getEntityTypeId(), $this->getHolder()->bundle()) == 1) {
      // For the user entity we might use ->getDisplayName()
      return $this->getHolder()->label();
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function doSave($id, EntityInterface $entity) {
    // Check the name length.
    if (strlen($this->name->value) > 64) {
      $this->name->value = substr($this->name->value, 0, 64);
      drupal_set_message(
        t('Wallet name was truncated to 64 characters: @name', ['@name' => $this->name->value]),
        'warning'
      );
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);
    $fields['holder_entity_type'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Holder type'))
      ->setDescription(t('The entity type of the wallet holder'))
      ->setSetting('max_length', 32);

    $fields['holder_entity_id'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Holder ID'))
      ->setDescription(t('The entity id of the wallet holder'));

    $fields['name'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Wallet name'))
      ->setDescription(t("Set by the wallet's owner"))
      ->setSetting('max_length', 64)
      ->setRequired(TRUE)
    // If we leave the default to be NULL it is difficult to filter with mysql.
      ->setConstraints(['NotNull' => []]);

    $fields['public'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Public'))
      ->setDescription(t("Visible to everyone with 'view public wallets' permission"))
      ->setDefaultValue(1);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created on'))
      ->setDescription(t('The time that the wallet was created.'))
      ->setRevisionable(FALSE);

    $fields['system'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('System'))
      ->setDescription(t('TRUE if this wallet is controlled by the system'))
      ->setDefaultValue(0);

    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public function getSummaries() {
    if (!$this->stats) {
      $transactionStorage = $this->entityTypeManager()->getStorage('mcapi_transaction');
      // Fill in the values of any unused, available currencies.
      foreach (mcapi_currencies_available($this) as $currency) {
        $curr_id = $currency->id();
        $this->stats[$curr_id] = $transactionStorage->walletSummary($curr_id, $this->id());
      }
    }
    return $this->stats;
  }

  /**
   * {@inheritdoc}
   */
  public function getStats($curr_id) {
    $summaries = $this->getSummaries();
    if (array_key_exists($curr_id, $summaries)) {
      return $summaries[$curr_id];
    }
  }

  /**
   * {@inheritdoc}
   */
  public function balance($curr_id, $display = Currency::DISPLAY_NORMAL, $linked = TRUE) {
    $stats = $this->getStats($curr_id);
    return Currency::load($curr_id)->format($stats['balance'], $display, $linked);
  }

  /**
   * {@inheritdoc}
   */
  public function getStat($curr_id, $stat) {
    $stats = $this->getStats($curr_id);
    if (array_key_exists($curr_id, $stats)) {
      return $stats[$curr_id];
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getStatAll($stat = 'balance') {
    $worth = [];
    foreach ($this->getSummaries() as $curr_id => $stats) {
      $worth[] = [
        'curr_id' => $curr_id,
        'value' => intval($stats[$stat]),
      ];
    }
    return $worth;
  }

  /**
   * {@inheritdoc}
   *
   * @todo move to static history class.
   */
  public function history($from = 0, $to = 0) {
    $query = $this->entityTypeManager()
      ->getStorage('mcapi_transaction')->getQuery()
      ->condition('involving', $this->id());
    if ($from) {
      $query->condition('created', $from, '>');
    }
    if ($to) {
      $query->condition('created', $from, '>');
    }
    return $query->execute();
  }

  /**
   * {@inheritdoc}
   */
  public function invalidateTagsOnSave($update) {
    parent::invalidateTagsOnSave($update);
    // Invalidate tags for the previous and current parents.
    $tags = [
      $this->holder_entity_type->value . ':' . $this->holder_entity_id->value,
    ];
    Cache::invalidateTags($tags);
  }

  /**
   * {@inheritdoc}
   */
  public function isIntertrading() {
    throw new exception('deprecated function Wallet::isIntertrading');
  }

  /**
   * {@inheritdoc}
   */
  public function getOwnerId() {
    return $this->getOwner()->id();
  }

  /**
   * {@inheritdoc}
   */
  public function getOwner() {
    $holder = $this->getHolder();
    return $holder instanceof UserInterface ?
      $holder :
      // All wallet holders, whatever entity type, implement OwnerInterface.
      $holder->getOwner();
  }

  /**
   * {@inheritdoc}
   */
  public function isVirgin() {
    $transactions = $this->entityTypeManager()
      ->getStorage('mcapi_transaction')->getQuery()
      ->condition('involving', $this->id())
      ->count()
      ->execute();
    return empty($transactions);
  }

}
