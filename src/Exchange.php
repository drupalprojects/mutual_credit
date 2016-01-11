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
use Drupal\user\EntityOwnerInterface;
use Drupal\Core\Entity\ContentEntityInterface;

use Drupal\mcapi_exchanges\Exchanges;


class Exchange {

  /**
   * Load currencies for a given ownerentity
   *
   * @param EntityOwnerInterface $entity
   *
   * @return Currency[]
   */
  public static function ownerEntityCurrencies(EntityOwnerInterface $entity = NULL) {
    return Self::MultipleExchanges() ?
      Exchanges::ownerEntityCurrencies($entity) :
      Currency::loadMultiple();
  }

  /**
   * get a list of all the currencies currently in a wallet's scope, ordered by weight
   *
   * @param \Drupal\user\UserInterface $account
   *
   * @return CurrencyInterface[]
   *   keyed by currency id
   *
   */
  public static function currenciesAvailableToUser(\Drupal\user\UserInterface $account = NULL) {
    if (!$account) {
      $account = User::load(\Drupal::currentUser()->id());
    }
    if (!isset($account->currencies_available)) {
      $currencies = Self::MultipleExchanges() ?
        Exchanges::currenciesAvailableToUser($account) :
        Currency::loadMultiple();

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
