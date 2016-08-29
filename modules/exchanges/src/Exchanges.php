<?php

namespace Drupal\mcapi_exchanges;

use Drupal\mcapi\Mcapi;
use Drupal\mcapi\Exchange;
use Drupal\group\Entity\Group;
use Drupal\group\Entity\GroupInterface;
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
    $exchanges = [];
    //@todo refactor this @see
    foreach (\Drupal::service('group.membership_loader')->loadByUser($entity) as $mem) {
      $group = $mem->getGroup();
      if ($group->getGroupType()->id() == 'exchange') {
        $exchanges[] = $group->id();
      }
    }
    return $exchanges;
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
    if (!$exchange->currencies){mtrace();}
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
    $exchange_ids = SELF::memberOf($account);
    if (empty($exchanges)) {
      drupal_set_message(t('%name is not in any exchanges', ['%name' => $account->getAccountName()]), 'error');
      $exchange_ids = \Drupal::entityQuery('group')->condition('type', 'exchange')->execute();
    }
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
   * Get all the wallets whose holders are members of a given exchange.
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
   */
  public static function walletsInExchange(array $exids) {
    drupal_set_message('how to get members AND content for a group?');
    $memberships = [];
    foreach ($exids as $group_id) {
      $memberships = array_merge(
        $memberships,
        \Drupal::service('group.membership_loader')->loadByGroup(Group::load($group_id))
      );
    }
    foreach ($memberships as $ship) {
      $holders['user'][] = $ship->getUser()->id();//users doesn't cover nodes.
    }
    // Not very elegant
    $holders['user'] = array_unique($holders['user']);
    //now get all the wallets held by these Entities.
    return Mcapi::walletsOfHolders($holders);
  }

  /**
   * {@inheritdoc}
   */
  public static function deletable() {
    if ($this->get('status')->value) {
      $this->reason = t('Exchange must be disabled');
      return FALSE;
    }
    $wid = SELF::getIntertradingWalletId();
    if (count(Wallet::load($wid)->history())) {
      $this->reason = t('Exchange intertrading wallet has transactions');
      return FALSE;
    }
    // If the exchange has wallets, even orphaned wallets, it can't be deleted.
    $wallet_ids = Mcapi::walletsOf($this);
    if (count($wallet_ids)) {
      $this->reason = t('The exchange still owns wallets: @nums', ['@nums' => implode(', ', $wallet_ids)]);
      return FALSE;
    }
    return TRUE;
  }

  /**
   * @todo
   */
  public static function getIntertradingWalletId($group) {
    return \Drupal::entityQuery('mcapi_wallet')
      ->condition('payways', Wallet::PAYWAY_AUTO)
      ->condition('holder_entity_id', $group->id())
      ->result();
//    $wallets = $this->entityTypeManager()
//      ->getStorage('mcapi_wallet')
//      ->loadByProperties(['payways' => Wallet::PAYWAY_AUTO, 'holder_entity_id' => $group->id()]);
//    return reset($wallets);
  }

  /**
   * Find out whether the exchange group can be deactivated.
   *
   * @param GroupInterface $group
   *   The 'exchange' group we are testing for
   *
   * @return bool
   *   TRUE if the exchange is active.
   *
   * @todo refactor this.
   */
  public static function deactivatable(GroupInterface $exchange) {
    return (bool)$exchange->active->value;
  }

  /**
   * Reverse lookup to see if a given user is a member of this exchange.
   *
   * @param GroupInterface $group
   *   The 'exchange' group we are testing for
   * @param AccountInterface $account
   *   The user who may be in the exchange
   *
   * @return bool
   *   TRUE if the account is in the group
   *
   * @todo rewrite if needed
   */
  public static function hasMember(GroupInterface $exchange, AccountInterface $account) {
    return (bool) \Drupal::service('group.membership_loader')->load($exchange, $account);
  }

  /**
   * Get all the members of an exchange.
   *
   * @param GroupInterface $group
   *   The 'exchange' group we are testing for.
   *
   * @return integer[]
   *   The users who are in the group.
   */
  public static function memberIds(GroupInterface $group) {
    foreach (\Drupal::service('group.membership_loader')->loadByGroup($group) as $membership) {
      $users[] = $membership->getuser($membership);
    }
    return $users;
  }


  /**
   * Get all content of a given bundle in an exchange.
   *
   * @param GroupInterface $group
   *   The 'exchange' group we are testing for.
   * @param string $entity_type_id
   *   The name of the entity we are counting.
   * @param GroupInterface $bundle
   *   The name of the bundle we are counting.
   *
   * @return array[]
   *   Arrays of entity ids, keyed by bundle
   *
   * @todo
   */
  public static function contentIds(GroupInterface $group, $entity_type_id, $bundle = NULL) {
    drupal_set_message('unable to get content ids yet');
    return [$entity_type_id => [1]];

    foreach (\Drupal::service('group.membership_loader')->loadByGroup($group) as $membership) {
      $content[$entity_type_id] = $membership->getuser($membership);
    }
    return $content;
  }


  /**
   * Get the number of transactions which happened in an exchange.
   *
   * @param GroupInterface $group
   *   The 'exchange' group we are testing for.
   * @param array $conditions
   *   Conditions for the transaction entityQuery.
   *
   * @return integer
   *   The number of transactions, by serial, in the exchange.
   */
  public static function transactionCount(GroupInterface $group, array $conditions = []) {

    $wallets = Exchanges::walletsInExchange([$group->id()]);
    $query = \Drupal::entityQuery('mcapi_transaction')
      ->condition('involving', $wallets);
    foreach ($conditions as $field => $val) {
      $operator = is_array($val) ? 'IN' : '=';
      $query->condition($field, $val, $operator);
    }
    $serials = $query->execute();
    return count(array_unique($serials));
  }
}
