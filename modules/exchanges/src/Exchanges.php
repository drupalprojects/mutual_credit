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
   * @todo this is pretty intensive and would benefit from caching and using sparingly
   */
  public static function walletsInExchange(GroupInterface $exchange) {
    static $i = 0; drupal_set_message('need to cache walletsInExchange? use '.$i++);
    $group_content_ids = \Drupal::entityQuery('group_content')
      ->condition('gid', $exchange->id())
      ->execute();
    if (empty($group_content_ids)) {
      \Drupal::logger('mcapi_exchanges')
        ->warning('No wallet holders found in exchange %ids', ['%ids' => $exchange->id()]);
      return [];
    }
    $holders = [];
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
    foreach ($holders as $entity_type_id => $ids) {
      $and = $query->andConditionGroup()
        ->condition('holder_entity_type', $entity_type_id)
        ->condition('holder_entity_id', $ids, 'IN');
      $or->condition($and);
    }
    $result = $query->condition($or)->execute();

    return $result->fetchCol();

    // Deprecated see above
    //return Mcapi::walletsOfHolders($holders);
  }

  /**
   * @todo
   */
  public static function getIntertradingWalletId($group) {
    return \Drupal::entityQuery('mcapi_wallet')
      ->condition('payways', Wallet::PAYWAY_AUTO)
      ->condition('holder_entity_id', $group->id())
      ->execute();
  }

}