<?php

/**
 * @file
 * 
 * Contains \Drupal\mcapi\Exchange.
 * The purpose of this class is to contain all the functions that vary when module
 * mcapi_exchanges is installed. 
 * In which case this is class is replaced by \Drupal\mcapi_exchanges\Exchanges
 * 
 * @todo make an interface for this
 */

namespace Drupal\mcapi;

use Drupal\user\Entity\User;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\mcapi\Entity\Currency;
use Drupal\mcapi\Entity\Wallet;

use Drupal\mcapi_exchanges\Exchanges;


class Exchange {
  
  /**
   * return a list of exchanges of which the passed entity is a member
   * If an exchange is passed, it returns itself
   *
   * @param ContentEntityInterface $entity
   *   Any Content Entity which is a member of an exchange
   *
   * @return integer[]
   *   Exchange entity ids
   *
   * @todo replace all uses of this with _og_get_entity_groups($entity_type = 'user', $entity = NULL, $states = array(OG_STATE_ACTIVE), $field_name = NULL)
   */
  static function in(ContentEntityInterface $entity = NULL, $enabled = TRUE, $open = FALSE) {
    if (\Drupal::moduleHandler()->moduleExists('mcapi_exchanges')) {
      return Exchanges::in($entity, $enabled, $open);
    }
    else {
      debug('improve this format');
      return array(0 => 0);
    }
  }

  /**
   * Get the exchanges which this wallet can be used in.
   * If the owner is an exchange return that exchange,
   * otherwise return the exchanges the owner is in.
   *
   * @param \Drupal\mcapi\Entity\Wallet $wallet
   *
   * @param boolean $open
   *   exclude closed exchanges
   *
   * @return integer[]
   *   keyed by entity id
   * 
   * @todo maybe this should return only exchange ids?
   * 
   * @deprecated replace with og_get_entity_groups($entity_type = 'user', $entity = NULL, $states = array(OG_STATE_ACTIVE), $field_name = NULL);
   */
  public static function walletInExchanges(Wallet $wallet, $open = FALSE) {
    if (\Drupal::moduleHandler()->moduleExists('mcapi_exchanges')) {
      return Exchanges::walletInExchanges($wallet, $open);
    }
    else {
      return $wallet->entity_type == 'mcapi_exchange' ?
        array($wallet->pid => $wallet->getOwner()) ://TODO how do exchanges own wallets if exchanges aren't an entity?
        Self::in($wallet->getOwner(), TRUE, $open);
    }
  }
  
  /**
   * Check if an entity is the owner of a wallet
   * @todo this is really a constant, but constants can't store arrays. What @todo?
   *
   * @return array
   *   THE list of permissions used by walletAccess. Note this is not connected
   *   to the roles/permissions system for account entity
   * @note this is only called once at the moment
   */
  public static function walletPermissions() {
    $perms = [
      //TODO only wallets owned by user entities can have this option
      WALLET_ACCESS_OWNER => t('The wallet owner'),
      // => ,//todo: which exchanges?
      WALLET_ACCESS_AUTH => t('Any logged in users'),
      WALLET_ACCESS_ANY => t('Anyone on the internet'),
      WALLET_ACCESS_USERS => t('Named users...')
    ];
    if (\Drupal::moduleHandler()->moduleExists('mcapi_exchanges')) {
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
    debug('how many times does this run in a request?', 'does it need cacheing?');
    $types = &drupal_static('walletableBundles');
    if (!$types) {
      if (0 && $cache = \Drupal::cache()->get('walletableBundles')) {
        $types = $cache->data;
      }
      else{
        if (\Drupal::moduleHandler()->moduleExists('mcapi_exchanges')) {
          $types = Exchanges::walletableBundles($wallet, $open);
        }
        else {
          foreach (array_keys(array_filter(\Drupal::Config('mcapi.wallets')->get('entity_types'))) as $entity_bundle) {
            list($entity, $bundle) = explode(':', $entity_bundle);
            $types[$entity][] = $bundle;
          }
        }
        \Drupal::cache()->set(
          'walletableBundles',
          $types,
          \Drupal\Core\Cache\CacheBackendInterface::CACHE_PERMANENT,
          []//TODO what cache tags to use if the cache is permanent?
        );
      }
    }
    return $types;
  }
  
   
  /*
   * identify a new parent entity for a wallet
   */
  public static function new_owner($owner) {
    if (\Drupal::moduleHandler()->moduleExists('mcapi_exchanges')) {
      return Exchanges::new_owner($owner);
    }
    else {
      return ['user', 1];
    }
  }
  
  /**
   * Load currencies for a given user
   * A list of all the currencies available to the current user
   *
   * @param AccountInterface $account
   *
   * @return array
   *   of currencies
   * 
   * @todo refactor this
   */
  public static function userCurrencies(AccountInterface $account = NULL) {
    if (\Drupal::moduleHandler()->moduleExists('mcapi_exchanges')) {
      $exchange_ids = Self::in($account, TRUE);
      return exchange_currencies($exchange_ids, 0);
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
   * @return array
   *   names of replicable elements in the transaction
   */
  public static function transactionTokens($include_virtual = FALSE) {
    $tokens = \Drupal::entityManager()
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
    if (\Drupal::moduleHandler()->moduleExists('mcapi_exchanges')) {
      $tokens[] = 'intertrade';
    }
    return $tokens;
  }
  
  
  /**
   * get a list of all the currencies currently in a wallet's scope
   * which is to say, in any of the wallet's parent's exchanges
   *
   * @param WalletInterface $wallet
   * 
   * @return CurrencyInterface[]
   *   keyed by currency id
   *
   * @todo consider moving to static class Exchanges
   */
  public static function currenciesAvailable($wallet) {
    if (!isset($wallet->currencies_available)) {
      $wallet->currencies_available = [];
      if (\Drupal::moduleHandler()->moduleExists('mcapi_exchanges')) {
        $exchanges = Exchanges::walletInExchanges($wallet);
        foreach (Exchange::currencies($exchanges) as $currency) {
          $wallet->currencies_available[$currency->id()] = $currency;
        }
      }
      else {
        $wallet->currencies_available = Currency::loadMultiple();
      }
    }
    //TODO get these in weighted order
    return $wallet->currencies_available;
  }
}
