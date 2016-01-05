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

use Drupal\user\Entity\User;
use Drupal\mcapi\Entity\Currency;
use Drupal\mcapi\Entity\Wallet;
use Drupal\user\EntityOwnerInterface;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Session\AccountInterface;

use Drupal\mcapi_exchanges\Exchanges;


class Exchange {

  /*
   * identify a new parent entity for a wallet
   */
  public static function FindNewHolder(ContentEntityInterface $previous_holder) {
    if (Self::MultipleExchanges()) {
      return Exchanges::findNewHolder($previous_holder);
    }
    else {
      return User::load(1);
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
   * @param AccountInterface $account
   *
   * @return CurrencyInterface[]
   *   keyed by currency id
   * 
   */
  public static function currenciesAvailableToUser(AccountInterface $account = NULL) {
    if (!$account) {
      $account = User::load(\Drupal::currentUser()->id());
    }
    if (!isset($account->currencies_available)) {
      $currencies = [];
      if (Self::MultipleExchanges()) {
        //get the currencies in all the wallet parent's exchanges
        $currencies = Exchanges::currenciesAvailableToUser($account);
      }
      else {
        $currencies = Currency::loadMultiple();
      }
      uasort($currencies, '\Drupal\mcapi\Mcapi::uasortWeight');
      $account->currencies_available = $currencies;
    }
    //note this adhoc variable gives us no control over static or caching
    return $account->currencies_available;
  }
 

  private static function multipleExchanges() {
    static $multiple = NULL;
    if (!isset($multiple)) {
      $multiple = \Drupal::moduleHandler()->moduleExists('mcapi_exchanges');
    }
    return $multiple;
  }
}
