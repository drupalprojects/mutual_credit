<?php

namespace Drupal\mcapi_exchanges;

use Drupal\mcapi\Entity\Wallet;
use Drupal\mcapi\Exchange;
use Drupal\group\Entity\GroupInterface;
use Drupal\user\Entity\User;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Entity\ContentEntityInterface;

/**
 * Static class.
 */
class Exchanges extends Exchange {

  static $walletsInExchanges = [];

  /**
   * Identify a new parent entity for a wallet.
   */
  public static function findNewHolder(ContentEntityInterface $previous_holder) {
    //if the previous holder is a group then revert the wallet to user 1
    if ($previous_holder->getEntityTypeId() == 'group') {
      return User::load(1);
    }
    if ($mem = group_exclusive_membership_get('exchange', $previous_holder)) {
      return $mem->getGroup();
    }
  }

  /**
   * Get a list of all the currencies currently in a user's scope.
   *
   * Which is to say, in any of the user's wallets' parent's exchanges.
   *
   * @param AccountInterface $account
   *   The user.
   *
   * @return CurrencyInterface[]
   *   Keyed by currency ID.
   */
  public static function currenciesAvailableToUser(AccountInterface $account = NULL) {
    if (is_null($account)) {
      $account = \Drupal::currentUser();
    }
    // The user is in one and only one exchange
    $user = User::load($account->id());
    $currencies = [];
    if ($membership = group_exclusive_membership_get('exchange', $user)) {
      foreach ($membership->getGroup()->currencies->referencedEntities() as $currency) {
        $currencies[$currency->id()] = $currency;
      }
    }
    return $currencies;
  }

  /**
   * The design of this module took a u-turn when I decided that every wallet is
   * in one and only one exchange, otherwise currency availability gets too
   * complicated. So the currencies available to a wallet are determined by its
   * holder, what what one exchange the holder is in.
   *
   * @param Wallet $wallet
   *
   * @return \Drupal\mcapi\Entity\Currency[]
   */
  public static function currenciesAvailable(Wallet $wallet) {
    if($memship = group_exclusive_membership_get('exchange', $wallet)) {
      return $memship->getGroup()->currencies->referencedEntities();
    }
    return [];
  }

  /**
   * Get all the wallets whose holders are members of the given exchange(s).
   *
   * This is where things get ugly but we've saved having to maintain a field
   * showing what wallets are in what exchanges
   *
   * @param GroupInterface $exchange
   *   The exchange whose wallet ids are needed
   *
   * @return int[]
   *   All the wallet ids in the given exchanges except intertrading
   *
   * @todo refactor this once wallets are in groups
   */
  public static function walletsInExchange(GroupInterface $exchange) {
    $contents = \Drupal::entityTypeManager()
      ->getStorage('group_content')
      ->loadByProperties(['gid'=> $exchange->id(), 'type' => 'exchange-wallet']);
    $wallet_ids = [];
    foreach ($contents as $content) {
      $wallet_ids[] = $content->getEntity()->id();
    }
    return $wallet_ids;
  }


}
