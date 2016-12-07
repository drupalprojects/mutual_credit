<?php

namespace Drupal\mcapi\Entity;

use Drupal\user\UserInterface;
use Drupal\mcapi\Exchange;
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

  // Anyone can pay into this wallet
  const PAYWAY_ANYONE_IN = 'I';
  // Anyone can pay out from this wallet
  const PAYWAY_ANYONE_OUT = 'O';
  // Anyone can pay into and out of this wallet
  const PAYWAY_ANYONE_BI = 'B';
  /**
   * Only system can interact with this wallet
   *
   * @var string
   */
  const PAYWAY_AUTO = 'A';

  private $holder;
  private $stats = [];
  private $access = [];

  /**
   * {@inheritdoc}
   */
  public static function payWays() {
    return [
      Self::PAYWAY_ANYONE_IN => t('Anyone can pay into this wallet'),
      Self::PAYWAY_ANYONE_OUT => t('Anyone can pay out of this wallet'),
      Self::PAYWAY_ANYONE_BI => t('Anyone can pay in or out of this wallet'),
    ];
  }

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

    // Now set the default permissions.
    if (!isset($values['payways'])) {
      $values['payways'] = \Drupal::config('mcapi.settings')->get('payways');
    }
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
      // This could be shorter to add a reference if it doesn't exist already.
      // @todo do we need to save to both fields?
      if (in_array($this->payways->value, [Wallet::PAYWAY_ANYONE_OUT, Wallet::PAYWAY_ANYONE_BI])) {
        $payees = $this->payees->referencedEntities();
        $payees[] = $this->getOwner();
        $payees = array_unique($payees);
        $this->payees->setValue($payees);
        drupal_set_message(t("Allowing owner of @type to be a payee for the @type's wallet", ['@type' => $this->holder_entity_type->value]));
      }
      if (in_array($this->payways->value, [Wallet::PAYWAY_ANYONE_IN, Wallet::PAYWAY_ANYONE_BI])) {
        $payers = $this->payers->referencedEntities();
        $payers[] = $this->getOwner();
        $this->payers->setValue($payers);
        drupal_set_message(t("Allowing owner of @type to be a payer for the @type's wallet", ['@type' => $this->holder_entity_type->value]));
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function autoName() {
    if (Mcapi::maxWalletsOfBundle($this->getHolder()->getEntityTypeId(), $this->getHolder()->bundle()) == 1
    || $this->payways->value == Self::PAYWAY_AUTO) {
      if ($this->payways->value == Self::PAYWAY_AUTO) {
        $label = $this->getHolder()->label();
        // When this module being installed as part of an installation profile.
        if ($label == 'placeholder-for-uid-1') {
          $label == 'System';
        }
        return $label . ' ' . t('Import/Export');
      }
      else {
        // For the user entity we might use ->getDisplayName()
        return $this->getHolder()->label();
      }
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
      ->setSetting('max_length', 32)
      ->setRequired(TRUE);

    $fields['holder_entity_id'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Holder ID'))
      ->setDescription(t('The entity id of the wallet holder'))
      ->setRequired(TRUE);

    $fields['name'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Wallet name'))
      ->setDescription(t("Set by the wallet's owner"))
      ->setSetting('max_length', 64)
      ->setRequired(TRUE)
    // If we leave the default to be NULL it is difficult to filter with mysql.
      ->setConstraints(['NotNull' => []]);

    $fields['payways'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Payment direction'))
      ->setDescription(t('Everybody can pay in, out, or both'))
      ->setSetting('length', 1);

    $fields['public'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Public'))
      ->setDescription(t("Visible to everyone with 'view public wallets' permission"))
      ->setDefaultValue(1);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created on'))
      ->setDescription(t('The time that the wallet was created.'))
      ->setRevisionable(FALSE);

    $fields['orphaned'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Orphaned'))
      ->setDescription(t('TRUE if this wallet is not currently held'))
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
      foreach (Exchange::currenciesAvailable($this) as $currency) {
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
  public function getStat($curr_id, $stat) {
    $stats = $this->getStats($curr_id);
    if (array_key_exists($curr_id, $stats)) {
      return $stats[$curr_id];
    }
  }

  /**
   * Get the balance(s) of the current wallet, in worth format.
   *
   * @param string $stat
   *   Which stat we want to receive.
   *
   * @return array
   *   Worth values in no particular order, each with curr_id and (raw) value.
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
    return $this->payways->value == Self::PAYWAY_AUTO;
  }

  /**
   * {@inheritdoc}
   */
  public function currenciesAvailable() {
    debug('disintermediated', 'this function is not needed any more');
    return Exchange::currenciesAvailable($this);
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
