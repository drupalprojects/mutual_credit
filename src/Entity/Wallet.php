<?php

/**
 * @file
 * Contains \Drupal\mcapi\Entity\Wallet.
 */

namespace Drupal\mcapi\Entity;

use Drupal\mcapi\Exchange;
use Drupal\mcapi\Entity\Currency;
use Drupal\mcapi\WalletInterface;
use Drupal\user\EntityOwnerInterface;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Session\AccountInterface;

/**
 * Defines the wallet entity.
 * The metaphor is not perfect because this wallet can hold a negaive balance.
 * A more accurate term, 'account', has another meaning in Drupal
 *
 * @note Wallet 'holders' could be any content entity, but the wallet owner is
 * always a user, typically the owner of the holder entity
 *
 * @note The canonical link is actually a view
 *
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
 *   label_callback = "Drupal\mcapi\Entity\Wallet::walletFormatName",
 *   entity_keys = {
 *     "id" = "wid",
 *     "uuid" = "uuid"
 *   },
 *   translatable = FALSE,
 *   links = {
 *     "canonical" = "/wallet/{mcapi_wallet}",
 *     "log" = "/wallet/{mcapi_wallet}/log",
 *     "inex" = "/wallet/{mcapi_wallet}/inex"
 *   },
 *   field_ui_base_route = "mcapi.admin_wallets"
 * )
 */
class Wallet extends ContentEntityBase implements WalletInterface {
  
  const ACCESS_ANY = '1';//this is the role id
  const ACCESS_AUTH = '2';//this is the role id
  const ACCESS_USERS = 'u';//user id is in the wallet access table
  const ACCESS_OWNER =  'o';//this is replaced with a named user MAYBE NOT NEEDED
  
  private $holder;
  private $stats = [];

  /**
   * {@inheritDoc}
   */
  public function getHolder() {
    if (!isset($this->holder)) {
      $this->holder = $this->entityManager()
        ->getStorage($this->entity_type->value)
        ->load($this->pid->value);
      //in the impossible event that there is no owner, set
      if (!$this->holder) {
        throw new \Exception('Holder of wallet ' . $this->id() . ' does not exist: ' . $this->entity_type->value . ' ' . $this->pid->value);
      }
    }
    return $this->holder;
  }

  /**
   * {@inheritdoc}
   */
  public function getOwner() {
    $holder = $this->getHolder();
    return $holder instanceof \Drupal\user\UserInterface ?
      $holder :
      //because all wallet holder, whatever entity type, implement OwnerInterface
      $holder->getOwner();
  }
  /**
   * {@inheritdoc}
   */
  public function setHolder(EntityOwnerInterface $entity) {
    $this->pid = $entity->id();
    $this->entity_type = $entity->getEntityTypeId();
  }

  /**
   * {@inheritdoc}
   */
  public static function heldBy(ContentEntityInterface $entity) {
    return \Drupal::EntityManager()
        ->getStorage('mcapi_wallet')
        ->filter(['holder' => $entity]);
  }


  /**
   * {@inheritdoc}
   */
  public function label($langcode = NULL, $full = TRUE) {
    return call_user_func($this->getEntityType()->getLabelCallback(), $this);
  }

  /**
   * entity_label_callback().
   * default callback for wallet
   * put the Holder entity's name with the wallet name in brackets if there is one
   */
  public static function walletFormatName($wallet) {
    $output = $wallet->getHolder()->label();//what happens if the wallet is orphaned?

    $name = $wallet->name->value;

    if ($name == '_intertrade') {
      $output .= ' '.t('Import/Export');
    }
    elseif($name) {
      $output .= ' ('.$name.')';
    }
    return $output;
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
      ->setLabel(t('Wallet name'))
      ->setDescription(t("Set by the wallet's owner"))
      ->addConstraint('max_length', 64)
      ->setSetting('default_value', array(0 => '')); //if we leave the default to be NULL it is difficult to filter with mysql
    //->setDisplayConfigurable('view', TRUE)
    //->setDisplayConfigurable('form', TRUE);

    $fields['entity_type'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Holder entity type'))
      ->setDescription(t('The timezone of this user.'))
      ->setSetting('max_length', 32)
      ->setRequired(TRUE);

    $fields['pid'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Holder entity ID'));

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
      ->setDescription(t('TRUE if this wallet is not currently held'))
      ->setSetting('default_value', array(0 => '0'));
    return $fields;
  }

  /**
   * {@inheritDoc}
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
      $this->stats = $this->entityManager()
        ->getStorage('mcapi_transaction')
        ->summaryData($this->id());
      //ensure there is a value for all available currencies.
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
    $stats = getStats($curr_id);
    if (array_key_exists($curr_id, $stats)) {
      return $stats[$curr_id];
    }
  }

  /**
   * {@inheritDoc}
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
   * {@inheritDoc}
   */
  public static function orphan(ContentEntityInterface $holder) {
    $new_holder_entity = Exchange::findNewHolder($holder);
    $wallet_ids = Self::entityManager()
      ->getStorage('mcapi_wallet')
      ->filter(['holder' => $holder]);
    foreach (Self::loadMultiple($wallet_ids) as $wallet) {
      $criteria = ['involving' => $wallet->id()];
      if (Self::entityManager()->getStorage('mcapi_transaction')->filter($criteria)) {
        $new_name = $this->t(
          "Formerly !name's wallet: !label", ['!name' => $wallet->label(), '!label' => $wallet->label(NULL, FALSE)]
        );
        $wallet->set('name', $new_name)
          ->set('entity_type', $new_holder_entity->getEntityTypeId())
          ->set('pid', $new_holder_entity->id())
          ->save();
        //@note this implies the number of wallets an exchange can own to be unlimited.
        //or more likely that this max isn't checked during orphaning
        drupal_set_message($this->t(
          "!name's wallets are now owned by exchange !exchange", [
          '!name' => $wallet->label(),
          '!exchange' => \Drupal::l($new_holder_entity->label(), $exchange->url())
            ]
        ));
        \Drupal::logger('mcapi')->notice(
          'Wallet @wid was orphaned to @entitytype @id', [
          '@wid' => $wallet->id(),
          '@entitytype' => $new_holder_entity->getEntityTypeId(),
          '@id' => $new_holder_entity->id()->id()
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
   * {@inheritDoc}
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
