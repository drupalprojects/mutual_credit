<?php

namespace Drupal\mcapi_exchanges;

use Drupal\mcapi\Mcapi;
use Drupal\mcapi\Exchange;
use Drupal\group\Entity\Group;
use Drupal\group\Entity\GroupContent;
use Drupal\user\Entity\User;
use Drupal\Core\Session\AccountInterface;
use Drupal\user\EntityOwnerInterface;

use Drupal\Core\Entity\ContentEntityInterface;

/**
 * Static class.
 */
class Exchanges extends Exchange {

  /**
   * Return exchanges of which the passed entity is a member.
   *
   * If an exchange is passed, it returns itself.
   *
   * @param OwnerEntityInterface $entity
   *   Any Content Entity which is a member of an exchange.
   *
   * @return integer[]
   *   Exchange entity ids.
   *
   * @todo check all calls to this are using the needed boolean args
   * @todo consider usering entityQuery directly
   */
  public static function memberOf($entity = NULL) {
    if (is_null($entity)) {
      $entity = User::load(\Drupal::currentUser()->id());
    }
    $exchange_ids = [];
    //@todo refactor this @see
    foreach (\Drupal::service('group.membership_loader')->loadByUser($entity) as $mem) {
      $group = $mem->getGroup();
      if ($group->getGroupType()->id() == 'exchange') {
        $exchange_ids[] = $group->id();
      }
    }
    return $exchange_ids;
  }

  /**
   * Identify a new parent entity for a wallet.
   */
  public static function findNewHolder(ContentEntityInterface $previous_holder) {
    if ($previous_holder->getEntityTypeId() == 'group') {
      return User::load(1);
    }
    $exchanges = SELF::memberOf($previous_holder);
    // If the parent entity was in more than one exchange, just pick the first.
    return reset($exchanges);
  }

  /**
   * Load currencies for a given user.
   *
   * @param EntityOwnerInterface $user
   *
   * @return CurrencyInterface[]
   */
  public static function ownerEntityCurrencies(EntityOwnerInterface $user = NULL) {
    return SELF::exchangeCurrencies(SELF::memberOf($user));
  }

  /**
   * Get all the currencies in the given exchanges.
   *
   * @param array $exchanges
   *
   * @return CurrencyInterface[]
   */
  public static function exchangeCurrencies(array $exchanges) {
    $currencies = [];
    foreach ($exchanges as $exchange) {
      foreach ($exchange->currencies->referencedEntities() as $currency) {
        $currencies[$currency->id()] = $currency;
      }
    }
    uasort($currencies, array('\Drupal\Component\Utility\SortArray', 'sortByWeightProperty'));
    return $currencies;
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
    if (!$account) {
      $account = \Drupal::currentUser();
    }
    $exchange_ids = static::memberOf($account);
//    if (empty($exchanges)) {
//      drupal_set_message(t('%name is not in any exchanges', ['%name' => $account->getAccountName()]), 'error');
//      $exchanges  = Exchange::loadMultiple();
//    }
    return Self::exchangeCurrencies(Group::loadMultiple($exchange_ids));
  }

  /**
   * Get all the currency ids for one or more exchanges.
   *
   * I don't think there is a way to do reverse lookup entity query.
   * So this is a bit of a hack, accessesing the entity reference field table directly.
   *
   * @param array $exchange_ids
   *
   * @return array
   *   the currency ids
   *
   * @todo might it be better to iterate though the exchange entities and
   * $exchange->get('currencies')->referencedEntities()
   * then make them unique?
   *
   * @todo make this entityQuery.
   */
  public static function getCurrenciesOfExchanges($exchange_ids) {
    // We need all the currencies referenced by these exchanges.
    return \Drupal::database()->select('groups__currencies', 'c')
      ->fields('c', ['currencies_target_id'])
      ->condition('entity_id', $exchange_ids, 'IN')
      ->execute()->fetchCol();
  }

  /**
   * Get all the exchanges using a particular currency.
   *
   * I don't think there is a way to do reverse lookup entity query.
   * So this is a bit of a hack, accessesing the entity reference field table directly.
   *
   * @param string $curr_id
   *
   * @return array
   *   the exchange ids
   */
  public static function getExchangesUsing($curr_id) {
    return \Drupal::database()->select('group__currencies', 'c')
      ->fields('c', ['entity_id'])
      ->condition('currencies_target_id', $curr_id)
      ->execute()->fetchCol();
  }

  /**
   * @todo move this to eserai
   */
  public static function exchangeLoadByCode($code) {
    $exchanges = \Drupal::entityTypeManager()
      ->getStorage('mcapi_exchange')
      ->loadByProperties(['code' => $code]);
    return reset($exchanges);
  }

  /**
   * Get all the wallets whose holders are members of the given exchange(s).
   *
   * This is where things get ugly but we've saved having to maintain a field
   * showing what wallets are in what exchanges
   *
   * @param array $exids
   *   Group IDs of one or more exchanges.
   *
   * @return int[]
   *   All the wallet ids in the given exchanges.
   *
   * @todo clarify and document whether this includes intertrading wallets. probably does
   * @todo this is pretty intensive and would benefit from caching and using sparingly
   */
  public static function walletsInExchanges(array $exids) {
    drupal_set_message('how to get members AND content for a group?');
    $holders = [];

    foreach (Group::loadMultiple($exids) as $exchange) {
      $group_content_ids = \Drupal::entityQuery('group_content')->condition('gid', $exchange->id())->execute();
      foreach (GroupContent::loadMultiple($group_content_ids) as $item) {
        $entity = $item->getEntity();
        $holders[$entity->getEntityTypeId()][$entity->id()] = $entity->id();//users doesn't cover nodes.
      }
    }
    if (empty($holders)) {
      \Drupal::logger('exchanges')->warning('No content found in exchange(s) %ids', ['%ids' => implode(', ', $exids)]);
      return [];
    }
    //now get all the wallets held by these Entities.
    return Mcapi::walletsOfHolders($holders);
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