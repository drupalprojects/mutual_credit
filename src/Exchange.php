<?php

namespace Drupal\mcapi;

use Drupal\mcapi\Entity\Wallet;
use Drupal\Core\Session\AccountInterface;
use Drupal\user\Entity\User;
use Drupal\mcapi\Entity\Currency;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\mcapi_exchanges\Exchanges;

/**
 * Static class relating to one or multiple exchanges.
 */
class Exchange {

  /**
   * Get a list of all the currencies currently in a wallet's scope.
   *
   * @param AccountInterface $account
   *   The user.
   *
   * @return CurrencyInterface[]
   *   Keyed by currency id.
   */
  public static function currenciesAvailableToUser(AccountInterface $account = NULL) {
    if (!$account) {
      $account = User::load(\Drupal::currentUser()->id());
    }
    if (!isset($account->currencies_available)) {
      $currencies = Self::MultipleExchanges() ?
        Exchanges::currenciesAvailableToUser($account) :
        Currency::loadMultiple();
      $account->currencies_available = $currencies;
    }
    // Note this adhoc variable gives us no control over static or caching.
    return $account->currencies_available;
  }

  /**
   * Get the currencies in the exchange of the wallet owner.
   *
   * @param Wallet $wallet
   *
   * @return \Drupal\mcapi\Entity\Currency[]
   *   NOT keyed by currency ID
   */
  public static function currenciesAvailable(Wallet $wallet) {
    // Either the wallet holder is an exchange, or is in an exchange
    $currencies  = Self::MultipleExchanges() ?
      Exchanges::currenciesAvailable($wallet) :
      Currency::loadMultiple();
    uasort($currencies, '\Drupal\mcapi\Mcapi::uasortWeight');
    return $currencies;
  }

  /**
   * Find out whether there is more than one exchange on this site.
   */
  private static function multipleExchanges() {
    static $multiple = NULL;
    if (!isset($multiple)) {
      $multiple = \Drupal::moduleHandler()->moduleExists('mcapi_exchanges');
    }
    return $multiple;
  }

  /**
   * Identify a new parent entity for a wallet.
   */
  public static function findNewHolder(ContentEntityInterface $previous_holder) {
    return User::load(1);
  }

}
