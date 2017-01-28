<?php

namespace Drupal\mcapi\Entity;

use Drupal\user\UserInterface;
use Drupal\mcapi\Mcapi;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
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

  private $stats = [];

  /**
   * {@inheritdoc}
   *
   * Holders with max 1 wallet pass their names onto their wallets, otherwise
   * use the name field, otherwise provide a generic default
   */
  public function label() {
    $holder = $this->getHolder();
    $max_wallets = Mcapi::maxWalletsOfBundle($holder->getEntityTypeId(), $holder->bundle());
    if ($max_wallets == 1) {
      $label = $holder->label();
    }
    elseif ($label = $this->name->value){}
    else {
      $label = t('Wallet #@num', ['@num' => $wallet->id()]);
    }

    return $label;
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

    $fields['bursers'] = BaseFieldDefinition::create('burser_reference')
      ->setLabel(t('Bursers'))
      ->setDescription(t('Nominated users, including the owner, who can use this wallet'))
      ->setSetting('target_type', 'user')
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE)
      ->setCardinality(-1);

    // Following are computed fields, just for display esp by views
    $fields['holder'] = BaseFieldDefinition::create('wallet_holder')
      ->setDescription(t("The entity holding this wallet"))
      ->setComputed(TRUE)
      ->setDisplayConfigurable('view', TRUE);
    // Might also like an owner field as well.
    $fields['balance'] = BaseFieldDefinition::create('worth')
      ->setLabel(t('Balance'))
      ->setDescription(t("Sum of all this wallet's credits minus debits"))
      ->setComputed(TRUE)
      ->setClass('\Drupal\mcapi\Plugin\Field\WalletBalance')  //$this->definition['class']
      ->setReadOnly(TRUE)
      ->setDisplayConfigurable('view', TRUE);
    $fields['volume'] = BaseFieldDefinition::create('worth')
      ->setLabel(t('Volume'))
      ->setDescription(t("Sum of all this wallet's transactions"))
      ->setComputed(TRUE)
      ->setClass('\Drupal\mcapi\Plugin\Field\WalletVolume')
      ->setReadOnly(TRUE)
      ->setDisplayConfigurable('view', TRUE);
    $fields['gross_in'] = BaseFieldDefinition::create('worth')
      ->setLabel(t('Gross income'))
      ->setDescription(t("Sum of all this wallet's income ever"))
      ->setComputed(TRUE)
      ->setClass('\Drupal\mcapi\Plugin\Field\WalletIncome')
      ->setReadOnly(TRUE)
      ->setDisplayConfigurable('view', TRUE);
    $fields['gross_out'] = BaseFieldDefinition::create('worth')
      ->setLabel(t('Gross expenditure'))
      ->setDescription(t("Sum of all this wallet's outgoings ever"))
      ->setComputed(TRUE)
      ->setClass('\Drupal\mcapi\Plugin\Field\WalletOutgoing')
      ->setReadOnly(TRUE)
      ->setDisplayConfigurable('view', TRUE);
    $fields['trades'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Trades'))
      ->setDescription(t("Number of transactions involving this wallet"))
      ->setComputed(TRUE)
      ->setClass('\Drupal\mcapi\Plugin\Field\WalletTrades')
      ->setReadOnly(TRUE)
      ->setDisplayConfigurable('view', TRUE);
    $fields['partners'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Number of partners'))
      ->setDescription(t("Number of unique trading partners"))
      ->setComputed(TRUE)
      ->setClass('\Drupal\mcapi\Plugin\Field\WalletPartners')
      ->setReadOnly(TRUE)
      ->setDisplayConfigurable('view', TRUE);

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

  public function getHolder() {
    return $this->holder->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function getOwner() {
    $holder_entity = $this->getHolder();

    return $holder_entity instanceof UserInterface ?
      $holder_entity :
      // All wallet holders, whatever entity type, implement OwnerInterface.
      $holder_entity->getOwner();
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
