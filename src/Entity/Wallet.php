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
use Drupal\Core\Cache\Cache;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Session\AccountInterface;
use Drupal\mcapi\Exchange;
use Drupal\mcapi\Entity\Currency;
use Drupal\mcapi\WalletInterface;

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
 *     "views_data" = "Drupal\mcapi\Views\WalletViewsData",
 *     "route_provider" = {
 *       "html" = "Drupal\mcapi\Entity\WalletRouteProvider",
 *     },
 *   },
 *   admin_permission = "configure mcapi",
 *   base_table = "mcapi_wallet",
 *   entity_keys = {
 *     "id" = "wid",
 *     "uuid" = "uuid"
 *   },
 *   translatable = FALSE,
 *   links = {
 *     "canonical" = "wallet/{mcapi_wallet}",
 *     "log" = "wallet/{mcapi_wallet}/log",
 *     "inex" = "wallet/{mcapi_wallet}/inex"
 *   },
 *   field_ui_base_route = "mcapi.admin_wallets"
 * )
 */
class Wallet extends ContentEntityBase implements WalletInterface {

  private $owner;
  private $stats = [];

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
        throw new \Exception('Owner of wallet ' . $this->id() . ' does not exist: ' . $this->entity_type->value . ' ' . $this->pid->value);
      }
    }
    //if for some reason there isn't an owner, return exchange 1 so as not to break things
    return $this->owner;
  }

  /**
   * {@inheritdoc}
   */
  public function ownerUserId() {
    $owner = $this->getOwner();
    if ($owner instanceof \Drupal\user\UserInterface)
      return $owner->id();
    //because all wallet owners, whatever entity type, implement OwnerInterface
    else
      return $owner->getOwnerId();
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
      $output = $this->getOwner()->label() . ": ";
    }
    if ($name == '_intertrade') {
      $output .= t('Import/Export');
    }
    elseif ($name) {
      $output .= $name;
    }

    return $output . ' #' . $this->wid->value;
  }

  /**
   * {@inheritdoc}
   */
  public static function preCreate(EntityStorageInterface $storage_controller, array &$values) {
    if (!array_key_exists('pid', $values) && array_key_exists('entity_type', $values)) {
      throw new Exception("new wallets must have an entity_type and a parent entity_id (pid)");
    }
    $values += array('name' => '', 'orphaned' => 0);
    //put the default values for the access here
    $access_settings = \Drupal::config('mcapi.wallets')->getRawData();
    foreach (Exchange::walletOps() as $op => $blurb) {
      if (!array_key_exists($op, $values)) {
        $values[$op] = key(array_filter($access_settings[$op]));
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function doSave($id, EntityInterface $entity) {
    //check the name length
    if (strlen($this->name->value) > 64) {
      $this->name->value = substr($this->name->value, 0, 64);
      drupal_set_message(t(
          'Wallet name was truncated to 64 characters: !name', array('!name' => $this->name->value))
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
      ->setSetting('default_value', array(0 => '')); //if we leave the default to be NULL it is difficult to filter with mysql
    //->setDisplayConfigurable('view', TRUE)
    //->setDisplayConfigurable('form', TRUE);

    $fields['entity_type'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Owner entity type'))
      ->setDescription(t('The timezone of this user.'))
      ->setSetting('max_length', 32)
      ->setRequired(TRUE);

    $fields['pid'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Owner entity ID'));

    $defaults = [
      'details' => 'o',
      'summary' => '2',
      'payout' => 'o',
      'payin' => '2',
    ];
    if ($access_settings = \Drupal::config('mcapi.wallets')->getRawData()) {
      foreach (array_keys($defaults) as $key) {
        $defaults[$key][2] = current(array_filter($access_settings[$key]));
      }
    }
    foreach (Exchange::walletOps() as $key => $info) {
      $fields[$key] = BaseFieldDefinition::create('string')
        ->setLabel($info[0])
        ->setDescription($info[1])
        ->setSetting('default_value', array($defaults[$key]))
        ->setSetting('length', 1);
    }
    $fields['orphaned'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Orphaned'))
      ->setSetting('default_value', array(0 => '0'));

    //@todo in beta2, this field is required by views. Delete if pos
    //$fields['langcode'] = BaseFieldDefinition::create('language')
     // ->setLabel(t('Language code'))
     // ->setDescription(t('language code.'))
     // ->setSettings(array('default_value' => 'und'));

    return $fields;
  }

  /**
   * get a list of all the currencies used and available to the wallet.
   */
  public function currencies_all() {
    //that means unused currencies should appear last
    return $this->currenciesUsed() + Exchange::currenciesAvailable($this);
  }

  /**
   * get a list of the currencies held in the wallet
   */
  public function currenciesUsed() {
    if (!$this->currencies_used) {
      $this->currencies_used = [];
      foreach (Currency::loadMultiple(array_keys($this->getSummaries())) as $currency) {
        $this->currencies_used[$currency->id()] = $currency;
      }
    }

    return $this->currencies_used;
  }

  /**
   * {@inheritDoc}
   */
  public function getSummaries() {
    if (!$this->stats) {
      $this->stats = $this->Entitymanager()->getStorage('mcapi_transaction')->summaryData($this->id());
      foreach (Exchange::currenciesAvailable($this) as $curr_id => $currency) {
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
  public function getStats($curr_id) {
    $summaries = $this->getSummaries();
    if (array_key_exists($curr_id, $summaries)) {
      return $summaries[$curr_id];
    }
  }

  /**
   * (non-PHPdoc)
   * @see \Drupal\mcapi\WalletInterface::getStat()
   */
  public function getStat($curr_id, $stat) {
    $stats = getStats($curr_id);
    if (array_key_exists($curr_id, $stats)) {
      return $stats[$curr_id];
    }
  }

  /**
   * {@inheritdoc}
   */
  //in parent, configEntityBase, $rel is set to edit-form by default - why would that be?
  //Is is assumed that every entity has an edit-form link? Any case this overrides it
  public function urlInfo($rel = 'canonical', array $options = []) {
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
   * (non-PHPdoc)
   * @see \Drupal\mcapi\WalletInterface::orphan()
   */
  public static function orphan(ContentEntityInterface $owner) {
    $new_owner_entity = Exchange::new_owner($owner);
    $wallet_ids = Self::entityManager()->getStorage('mcapi_wallet')->getOwnedIds($owner);
    foreach (entity_load('mcapi_wallet', $wallet_ids) as $wallet) {
      $criteria = ['involving' => $wallet->id()];
      if (Self::entityManager()->getStorage('mcapi_transaction')->filter($criteria)) {

        $new_name = $this->t(
          "Formerly !name's wallet: !label", ['!name' => $wallet->label(), '!label' => $wallet->label(NULL, FALSE)]
        );
        $wallet->set('name', $new_name)
          ->set('entity_type', $new_owner_entity->getEntityTypeId())
          ->set('pid', $new_owner_entity->id())
          ->save();
        //@todo this implies the number of wallets an exchange can own to be unlimited.
        //or more likely that this max isn't checked during orphaning
        drupal_set_message($this->t(
          "!name's wallets are now owned by exchange !exchange", [
          '!name' => $wallet->label(),
          '!exchange' => \Drupal::l($new_owner_entity->label(), $exchange->url())
            ]
        ));
        \Drupal::logger('mcapi')->notice(
          'Wallet @wid was orphaned to @entitytype @id', [
          '@wid' => $wallet->id(),
          '@entitytype' => $new_owner_entity->getEntityTypeId(),
          '@id' => $new_owner_entity->id()->id()
          ]
        );
      }
      else {
        $wallet->delete();
        return;
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function ownedBy(ContentEntityInterface $entity) {
    return Self::EntityManager()
        ->getStorage('mcapi_wallet')
        ->getOwnedIds($entity);
  }

  /**
   * {inheritdoc}
   * overrides parent function to also invalidate the wallet's parent's tag
   */
  public function invalidateTagsOnSave($update) {
    //invalidate the parent, especially the entity view, see mcapi_entity_view()
    $tags = Cache::mergeTags(
        $this->getEntityType()->getListCacheTags(), [$this->entity_type->value . ':' . $this->pid->value]
    );
    if ($update) {
      // An existing entity was updated, also invalidate its unique cache tag.
      $tags = Cache::mergeTags($tags, $this->getCacheTags());
    }
    Cache::invalidateTags($tags);
  }

}
