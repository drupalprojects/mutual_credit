<?php

/**
 * @file
 * Contains \Drupal\mcapi\Entity\Wallet.
 */

namespace Drupal\mcapi\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\mcapi\Entity\Exchange;
use Drupal\mcapi\Entity\Currency;
use Drupal\mcapi\WalletInterface;
use Drupal\Core\Cache\Cache;

/**
 * Defines the wallet entity.
 * NB This does not implement the ownerInterface because
 * wallets can belong to ANY content entities.
 * NB the canonical link is actually a 'views' page
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
 *     },
 *     "views_data" = "Drupal\mcapi\Views\WalletViewsData"
 *   },
 *   admin_permission = "configure mcapi",
 *   base_table = "mcapi_wallet",
 *   entity_keys = {
 *     "id" = "wid",
 *     "uuid" = "uuid"
 *   },
 *   translatable = FALSE,
 *   links = {
 *     "canonical" = "mcapi.wallet_view",
 *     "log" = "mcapi.wallet_log"
 *   },
 *   field_ui_base_route = "mcapi.admin_wallets"
 * )
 */
class Wallet extends ContentEntityBase  implements WalletInterface{

  private $owner;
  private $stats = array();
  //access settings
  private $access;
  
  static function ___create(array $values = array()) {
    //trying to inject storage or even entityManager
    echo ('wallet::create');mdump($values);
    return $values;
  }
  static function createInstance(array $values = array()) {
    //trying to inject storage or even entityManager
    die('Wallet::createInstance');
  }

  /**
   * {@inheritdoc}
   * @todo update this in line with https://drupal.org/node/2221879
   */
  public function uri() {
    return array(
      'path'=> 'wallet/'.$this->id()
    );
  }

  /**
   * get the wallet's owner, as determined by the wallet's own properties.
   */
  public function getOwner() {
    if (!isset($this->owner)) {
      $this->owner = $this->entityManager()
        ->getStorage($this->entity_type->value)
        ->load($this->pid->value);
      //in the impossible event that there is no owner, set
      if (!$this->owner) {
        throw new \Exception('Owner of wallet '. $this->id() .' does not exist: '.$this->entity_type->value .' '.$this->pid->value);
      }
    }
    //if for some reason there isn't an owner, return exchange 1 so as not to break things
    return $this->owner;
  }

  /**
   * {@inheritdoc}
   */
  public function user_id() {
    $owner = $this->getOwner();
    if ($owner instanceof \Drupal\user\UserInterface) return $owner->id();
    //because all wallet owners, whatever entity type, implement OwnerInterface
    else return $owner->getOwnerId();
  }

  /**
   * {@inheritdoc}
   */
  public function label($langcode = NULL, $full = TRUE) {
    //normally you would display the $full name to all users and the wallet->name only to the owner.
    $output = '';
    //we need to decide whether / when to display the owner and when the wallet name

    $name = $this->name->value;
    if ($full || !$name) {
      $output = $this->getOwner()->label() .": ";
    }
    if ($name == '_intertrade') {
      $output .= t('Import/Export');
    }
    elseif ($name) {
      $output .= $name;
    }

    return $output.' #'.$this->wid->value;
  }



  /**
   * {@inheritdoc}
   */
  public static function preCreate(EntityStorageInterface $storage_controller, array &$values) {
    if (!array_key_exists('pid', $values) && array_key_exists('entity_type', $values)) {
      throw new Exception("new wallets must have an entity_type and a parent entity_id (pid)");
    }
    $values += array('name' => '');
    //put the default values for the access here
    $access_settings = \Drupal::config('mcapi.wallets')->getRawData();
    foreach (Wallet::ops() as $op) {
      if (!array_key_exists($op, $values)) {
        $values[$op] = key(array_filter($access_settings[$op]));
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  function doSave($id, EntityInterface $entity) {
    //check the name length
    if (strlen($this->name->value) > 64) {
      $this->name->value = substr($this->name->value, 0, 64);
      drupal_set_message(t(
        'Wallet name was truncated to 64 characters: !name',
        array('!name' => $this->name->value))
      , 'warning');
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields['wid'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Wallet ID'))
      ->setDescription(t('The unique wallet ID.'))
      ->setReadOnly(TRUE)
      ->setSetting('unsigned', TRUE);

    $fields['uuid'] = BaseFieldDefinition::create('uuid')
      ->setLabel(t('UUID'))
      ->setDescription(t('The wallet UUID.'))
      ->setReadOnly(TRUE);

    $fields['name'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Name'))
      ->setDescription(t("The owner's name for this wallet"))
      ->addConstraint('max_length', 64)
      ->setDisplayOptions(
        'view',
        array('label' => 'hidden', 'type' => 'string', 'weight' => -5))
      ->setSetting('default_value', array(0 => ''));//if we leave the default to be NULL it is difficult to filter with mysql

    $fields['entity_type'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Owner entity type'))
      ->setDescription(t('The timezone of this user.'))
      ->setSetting('max_length', 32)
      ->setRequired(TRUE);

    $fields['pid'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Owner entity ID'));

    $defaults = array(
      'details' => array(t('Details'), t('Access code for viewing transaction details'), 'e'),
      'summary' => array(t('Details'), t('Access code for viewing wallet summary'), '2'),
      'payout' => array(t('Details'), t('Access code for paying in'), 'o'),
      'payin' => array(t('Details'), t('Access code for paying out'), 'e')
    );
    if ($access_settings = \Drupal::config('mcapi.wallets')->getRawData()) {
      foreach (array_keys($defaults) as $key) {
        $defaults[$key][2] = current(array_filter($access_settings[$key]));
      }
    }
    foreach ($defaults as $key => $info) {
      $fields[$key] = BaseFieldDefinition::create('integer')
        ->setLabel($info[0])
        ->setDescription($info[1])
        ->setSetting('default_value', array($defaults[$key][2]));
    }

    $fields['orphaned'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Orphaned'));
    
    //TODO in beta2, this field is required by views. Delete if pos
    $fields['langcode'] = BaseFieldDefinition::create('language')
    ->setLabel(t('Language code'))
    ->setDescription(t('language code.'))
    ->setSettings(array('default_value' => 'und'));

    return $fields;
  }

  /**
   * get the exchanges which this wallet can be used in.
   *
   * @param boolean $open
   *   filter for open exchanges only
   *
   * @return \Drupal\mcapi\Entity\Exchange[]
   *   keyed by entity id
   */
  function in_exchanges($open = FALSE) {
    return $this->entity_type == 'mcapi_exchange' ?
      array($this->pid => $this->getOwner()) :
      Exchange::referenced_exchanges($this->getOwner(), TRUE, $open);
  }

  /**
   * get a list of all the currencies used and available to the wallet.
   */
  function currencies_all() {
    //that means unused currencies should appear last
    return $this->currencies_used() + $this->currencies_available();
  }

  /**
   * get a list of the currencies held in the wallet
   */
  function currencies_used() {
    if (!$this->currencies_used) {
      $this->currencies_used = array();
      foreach (Currency::loadMultiple(array_keys($this->getSummaries())) as $currency) {
        $this->currencies_used[$currency->id()] = $currency;
      }
    }

    return $this->currencies_used;
  }

  /**
   * get a list of all the currencies in this wallet's scope
   */
  function currencies_available() {
    if (!isset($this->currencies_available)) {
      $this->currencies_available = array();
      foreach (exchange_currencies($this->in_exchanges()) as $currency) {
        $this->currencies_available[$currency->id()] = $currency;
      }
    }
    //TODO get these in weighted order
    return $this->currencies_available;
  }

  /**
   * {@inheritDoc}
   */
  function getSummaries() {
    if (!$this->stats) {
      $this->stats = $this->Entitymanager()->getStorage('mcapi_transaction')->summaryData($this->id());
      foreach ($this->currencies_available() as $curr_id => $currency) {
        if (!array_key_exists($curr_id, $this->stats)) {
          $this->stats[$curr_id] = array(
            'balance' => 0,
            'trades' => 0,
            'volume' => 0,
            'gross_in' => 0,
            'gross_out' => 0,
            'partners' => 0
          );
        }
      }
    }
    return $this->stats;
  }

  /**
   * (non-PHPdoc)
   * @see \Drupal\mcapi\WalletInterface::getStats()
   */
  function getStats($curr_id) {
    $summaries = $this->getSummaries();
    if (array_key_exists($curr_id, $summaries)) {
      return $summaries[$curr_id];
    }
  }

  /**
   * (non-PHPdoc)
   * @see \Drupal\mcapi\WalletInterface::getStat()
   */
  function getStat($curr_id, $stat) {
    $stats = getStats($curr_id);
    if (array_key_exists($curr_id, $stats)) return $stats[$curr_id];
    //else return NULL
  }


  /**
   * {@inheritdoc}
   */
  //in parent, configEntityBase, $rel is set to edit-form by default - why would that be?
  //Is is assumed that every entity has an edit-form link? Any case this overrides it
  public function urlInfo($rel = 'canonical', array $options = array()) {
    return parent::urlInfo($rel);
  }

  /**
   *
   * @return array
   *   all transactions between the times given
   */
  public function history($from = 0, $to = 0) {
    $conditions = array(
      'involving' => $this->id()
    );
    if ($from) {
      $conditions['from'] = $from;
    }
    if ($to) {
      $conditions['to'] = $to;
    }
    return $this->entitymanager()->getStorage('mcapi_transaction')->filter($conditions);
  }
  
  /**
   * @see \Drupal\mcapi\Storage\WalletInterface::spare()
   */
  public static function spare(ContentEntityInterface $owner) {
    //check the number of wallets already owned against the max for this entity type
    $bundle = $owner->getEntityTypeId().':'.$owner->bundle();
    $max = \Drupal::config('mcapi.wallets')->get('entity_types.'.$bundle);
    $owned = \Drupal\mcapi\Storage\WalletStorage::getOwnedIds($owner);
    return count($owned) < $max;
  }

  /**
   * (non-PHPdoc)
   * @see \Drupal\mcapi\WalletInterface::orphan()
   */
  static function orphan(ContentEntityInterface $owner) {
    if($exchanges = Exchange::referenced_exchanges($owner, FALSE)) {
      $exchange = current($exchanges);//if the parent entity was in more than one exchange, this will pick a random one to take ownership
      if ($wids = \Drupal\mcapi\Storage\WalletStorage::getOwnedIds($owner, TRUE)) {
        foreach (Wallet::loadMultiple($wids) as $wallet) {
          //if there are transactions change parent, otherwise delete
          if (\Drupal\mcapi\Storage\TransactionStorage::filter(array('involving' => $wallet->id()))) {
            $new_name = t(
              "Formerly !name's wallet: !label",
              array('!name' => $wallet->label(), '!label' => $wallet->label(NULL, FALSE))
            );
            $wallet->set('name', $new_name);
            $wallet->set('entity_type', 'mcapi_exchange');
            $wallet->set('pid', $exchange->id());
            //TODO make the number of wallets an exchange can own to be unlimited.
            drupal_set_message(t(
              "!name's wallets are now owned by exchange !exchange",
              array('!name' => $wallet->label(), '!exchange' => \Drupal::l($exchange->label(), $exchange->url()))
            ));
            $wallet->save();
          }
          else {
            $wallet->delete();
          }
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  static function ownedBy(ContentEntityInterface $entity) {
    return \Drupal::EntityManager()
      ->getStorage('mcapi_wallet')
      ->getOwnedIds($entity);
  }
  
  /**
   * {inheritdoc}
   * overrides parent function to also invalidate the wallet's parent's tag
   */
  protected function invalidateTagsOnSave($update) {
    $tags = $this->getEntityType()->getListCacheTags();
    //invalidate the parent, especially the entity view, see mcapi_entity_view()
    $tags = Cache::mergeTags($tags, array($this->entity_type->value.':'.$this->pid->value));
    if ($update) {
      // An existing entity was updated, also invalidate its unique cache tag.
      $tags = Cache::mergeTags($tags, $this->getCacheTag());
    }
    Cache::invalidateTags($tags);
  }

  /**
   * Check if an entity is the owner of a wallet
   * @todo this is really a constant, but constants can't store arrays. What @todo?
   *
   * @return array
   *   THE list of permissions used by walletAccess. Note this is not connected
   *   to the roles/permissions system for account entity
   */
  public static function permissions() {
    return array(
      //TODO only wallets owned by user entities can have this option
      WALLET_ACCESS_OWNER => t('Just the owner'),
      WALLET_ACCESS_EXCHANGE => t('Members in the same exchange(s)'),//todo: which exchanges?
      WALLET_ACCESS_AUTH => t('Any logged in users'),
      WALLET_ACCESS_ANY => t('Anyone on the internet'),
      WALLET_ACCESS_USERS => t('Named users...')
    );
  }

  /**
   * Check if an entity is the owner of a wallet
   * @todo this is really a constant, but constants can't store arrays. What @todo?
   *
   * @return array
   *   THE list of ops because arrays cannot be stored in constants
   */
  public static function ops() {
    return array('details', 'summary', 'payin', 'payout');
  }
}
