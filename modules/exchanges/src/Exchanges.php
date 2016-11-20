<?php

namespace Drupal\mcapi_exchanges;

use Drupal\mcapi\Entity\Wallet;
use Drupal\mcapi\Exchange;
use Drupal\group\Entity\GroupInterface;
use Drupal\group\Entity\GroupContent;
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
    $holder = $wallet->getHolder();
    if ($holder->getEntityTypeId() == 'group' && ($holder->bundle() == 'exchange')) {
      $exchange = $holder;
    }
    elseif($memship = group_exclusive_membership_get('exchange', $holder)) {
      $exchange = $memship->getGroup();
    }
    else {// The system intertrading wallet should be removed.
      throw new \Exception('No currencies are available.');
    }
    return $exchange->currencies->referencedEntities();
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
    $ids = \Drupal::entityQuery('group_content')
      ->condition('type', 'exchange-wallet')
      ->condition('gid', $exchange->id())
      ->execute();
    $wids = [];
    foreach(GroupContent::loadMultiple($ids) as $id => $group_content) {
      $wallet = $group_content->getEntity();
      if ($wallet->payways->value != Wallet::PAYWAY_AUTO) {
        $wids[] = $wallet->id();
      }
    }
    //return $wids;


    if (!isset(static::$walletsInExchanges[$exchange->id()])) {
      // Get all the ids of content in the exchange.
      $group_content_ids = \Drupal::entityQuery('group_content')
        ->condition('gid', $exchange->id())
        ->execute();
      if (empty($group_content_ids)) {
        \Drupal::logger('mcapi_exchanges')
          ->warning('No wallet holders found in exchange %ids', ['%ids' => $exchange->id()]);
        return [];
      }
      $holders = [];
      // Load ALL the content in the exchange.
      foreach (GroupContent::loadMultiple($group_content_ids) as $item) {
        $entity = $item->getEntity();
        $holders[$entity->getEntityTypeId()][$entity->id()] = $entity->id();//users doesn't cover nodes.
      }
      //This used to be walletsOfHolders
      // Build a query for all the available wallet IDs
      $query = \Drupal::database()->select('mcapi_wallet', 'w')
        ->fields('w', ['wid'])
        ->condition('orphaned', 0)
        ->condition('payways', Wallet::PAYWAY_AUTO, '<>');

      $or = $query->orConditionGroup();
      // Find the wallets whose holders are content in the exchange.
      foreach ($holders as $entity_type_id => $ids) {
        $and = $query->andConditionGroup()
          ->condition('holder_entity_type', $entity_type_id)
          ->condition('holder_entity_id', $ids, 'IN');
        $or->condition($and);
      }
      $query->condition($or);
      static::$walletsInExchanges[$exchange->id()] = $query->execute()->fetchCol();
    }
    return static::$walletsInExchanges[$exchange->id()];
  }

  /**
   * @todo
   */
  public static function getIntertradingWalletId($group) {
    // EntityQuery filters out intertrading wallets.
    $wallets = \Drupal::entityTypeManager()
      ->getStorage('mcapi_wallet')
      ->loadByProperties([
          'payways' => Wallet::PAYWAY_AUTO,
          'holder_entity_type' => 'group',
          'holder_entity_id' => $group->id()
        ]);
    return reset($wallets)->id();
  }

}
