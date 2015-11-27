<?php

/**
 * @file
 *
 * Contains \Drupal\mcapi\Exchange.
 * The purpose of this class is to contain all the functions that vary when module
 * mcapi_exchanges is installed.
 * In which case this is class is replaced by \Drupal\mcapi_exchanges\Exchanges
 *
 */

namespace Drupal\mcapi;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\mcapi\Entity\Currency;
use Drupal\mcapi\Entity\Wallet;
use Drupal\mcapi\Entity\WalletInterface;

use Drupal\mcapi_exchanges\Exchanges;


class Exchange {

  static private $walletableBundles;
  /**
   * Get all the possible Wallet permissions
   *
   * @return array
   *   THE list of permissions used by walletAccess. Note this is not connected
   *   to the roles/permissions system for account entity
   * @note this is only called once at the moment
   */
  public static function walletPermissions() {
    $perms = [
      Wallet::ACCESS_MEMBERS => t('Any logged in users'),
      Wallet::ACCESS_ANY => t('Anyone on the internet'),
      Wallet::ACCESS_USERS => t('Named users...'),
      Wallet::ACCESS_OG => t('Members of the same organic groups')
    ];
    if (Self::MultipleExchanges()) {
      //we could take this to the parent::walletPermissions()...
      $perms[WALLET_ACCESS_EXCHANGE] = t('Members in the same exchange(s)');
    }
    return $perms;
  }

  /**
   * Get a list of entities
   * which is to say, whether it is a contentEntity with an audience field
   *
   * @param unknown $entity_type
   *   an entity OR entityType object
   *
   * @param string $bundle
   *   bundlename, if the first arg is an entityType
   *
   * @return NULL  | string
   *   TRUE means the entitytype can hold wallets
   *
   * @todo update function and documentation after OG is integrated
   */
  public static function walletableBundles() {

    //@todo is there a usual way of working with static variables in static functions?
    if (!Self::$walletableBundles) {
      if (0 && $cache = \Drupal::cache()->get('walletableBundles')) {
        Self::$walletableBundles = $cache->data;
      }
      else{
        if (Self::MultipleExchanges()) {
          Self::$walletableBundles = Exchanges::walletableBundles($wallet, $open);
        }
        else {
          $entityTypes = \Drupal::Config('mcapi.settings')->get('entity_types');
          foreach (array_keys(array_filter($entityTypes)) as $entity_bundle) {
            list($type, $bundle) = explode(':', $entity_bundle);
            Self::$walletableBundles[$type][] = $bundle;
          }
        }
        \Drupal::cache()->set(
          'walletableBundles',
          Self::$walletableBundles,
          \Drupal\Core\Cache\CacheBackendInterface::CACHE_PERMANENT
        );
      }
    }
    return Self::$walletableBundles;
  }


  /*
   * identify a new parent entity for a wallet
   */
  public static function FindNewHolder(ContentEntityInterface $previous_holder) {
    if (Self::MultipleExchanges()) {
      return Exchanges::newHolder($previous_holder);
    }
    else {
      return ['user', 1];
    }
  }

  /**
   * Load currencies for a given ownerentity
   *
   * @param EntityOwnerInterface $account
   *
   * @return Currency[]
   */
  public static function ownerEntityCurrencies(EntityOwnerInterface $entity = NULL) {
    if (Self::MultipleExchanges()) {
      return Exchanges::ownerEntityCurrencies($entity);
    }
    else {
      return Currency::loadMultiple();
    }
  }

  /**
   * Identify the currencies which can be used by a given wallet
   *
   * @param walletInterface $wallet
   *
   * @return Currency[]
   *
   * @deprecated this is only used in mcapi_tester.module to generate transactions
   *
   */
  public static function walletCurrencies(walletInterface $wallet = NULL) {
    if (Self::MultipleExchanges()) {
      return Exchanges::walletCurrencies($wallet);
    }
    else {
      return Currency::loadMultiple();
    }
  }

  /**
   * helper function to get the token names for helptext token service and twig
   * get the entity properties from mcapi_token_info, then the fieldapi fields
   * this function would be handy for any entity_type, so something equivalent may exist already
   *
   * @param boolean
   *   if TRUE the result will include tokens to non-fields, such as the transition links
   *
   * @return string[]
   *   names of replicable elements in the transaction
   */
  public static function transactionTokens($include_virtual = FALSE) {
    $tokens = \Drupal::service('entity_field.manager')
      ->getFieldDefinitions('mcapi_transaction', 'mcapi_transaction');
    unset(
      $tokens['uuid'],
      $tokens['xid'],
      $tokens['parent'],
      $tokens['type'],
      $tokens['children']
    );
    $tokens = array_keys($tokens);

    if ($include_virtual){
      $tokens[] = 'url';
    }
    if (Self::MultipleExchanges()) {
      $tokens[] = 'intertrade';
    }
    return $tokens;
  }

  /**
   * get a list of all the currencies currently in a wallet's scope, ordered by weight
   *
   * @param WalletInterface $wallet
   *
   * @return CurrencyInterface[]
   *   keyed by currency id
   */
  public static function currenciesAvailable($wallet) {
    if (!isset($wallet->currencies_available)) {
      if (Self::MultipleExchanges()) {
        //get the currencies in all the wallet parent's exchanges
        Exchanges::currenciesAvailable($wallet);
      }
      else {
        $wallet->currencies_available = Currency::loadMultiple();
      }
    }
    if (count($wallet->currencies_available) > 1) {
      uasort($wallet->currencies_available, 'mcapi_uasort_weight');
    }
    return $wallet->currencies_available;
  }

  /**
   * Give back the operations which can be done on wallets
   *
   * @return array
   *   THE list of ops because arrays cannot be stored in constants
   */
  public static function walletOps() {
    $ops = [
      Wallet::OP_DETAILS => [
        t('View transaction log'),
        t('View individual transactions this wallet was involved in')
      ],
      Wallet::OP_SUMMARY => [
        t('View summary'),
        t('The balance, number of transactions etc.')
      ],
      Wallet::OP_PAYIN => [
        t('Pay in'),
        t('Create payments into this wallet')
      ],
      Wallet::OP_PAYOUT => [
        t('Pay out'),
        t('Create payments out of this wallet')
      ]
    ];
    if (Self::MultipleExchanges()) {
      $ops += Exchanges::walletOps();
    }
    return $ops;
  }

  private static function multipleExchanges() {
    static $multiple = NULL;
    if (!isset($multiple)) {
      $multiple = \Drupal::moduleHandler()->moduleExists('mcapi_exchanges');
    }
    return $multiple;
  }
}
