<?php

/**
 * @file
 *
 * Contains \Drupal\mcapi_exchanges\Exchanges.
 * Replaces \Drupal\mcapi\Exchanges
 *
 */

namespace Drupal\mcapi_exchanges;

use Drupal\mcapi\Mcapi;
use Drupal\mcapi\Exchange;
use Drupal\og\Og;
use Drupal\og\OgMembershipInterface;
use Drupal\user\Entity\User;
use Drupal\user\EntityOwnerInterface;

use Drupal\Core\Entity\ContentEntityInterface;


class Exchanges extends Exchange {

  /**
   * return exchanges of which the passed entity is a member
   * If an exchange is passed, it returns itself
   *
   * @param OwnerEntityInterface $entity
   *   Any Content Entity which is a member of an exchange
   *
   * @return integer[]
   *   Exchange entity ids
   *
   * @todo check all calls to this are using the needed boolean args
   * @todo consider usering entityQuery directly
   */
  static function memberOf(ContentEntityInterface $entity = NULL, $enabledOnly = FALSE, $openOnly = FALSE, $visibleOnly = FALSE) {
    if (!is_object($entity)) {
      $entity = User::load(\Drupal::currentUser()->id());
    }
    $in = Og::getEntityGroups($entity, [OgMembershipInterface::STATE_ACTIVE], EXCHANGE_OG_FIELD);
    drupal_set_message('checking membership of entity '.$entity->label() .'. does this need static?', 'warning');
    $result = [];
    if (isset($in['mcapi_exchange'])) {
      foreach ($in['mcapi_exchange'] as $id => $exchange) {
        if ($enabledOnly && !$exchange->status->value) continue;
        if ($openOnly && !$exchange->open->value) continue;
        if ($visibleOnly && !$exchange->visible->value) continue;
        $result[$id] = $exchange;
      }
    }
    return $result;
  }

  /*
   * identify a new parent entity for a wallet
   */
  public static function findNewHolder(ContentEntityInterface $previous_holder) {
    if ($previous_holder->getEntityTypeId() == 'mcapi_exchange') {
      return User::load(1);
    }
    $exchanges = Exchanges::memberOf($previous_holder, TRUE);
    //if the parent entity was in more than one exchange, just pick the first
    return reset($exchanges);
  }

  /**
   * Load currencies for a given user
   * A list of all the currencies available to the current user
   *
   * @param EntityOwnerInterface $entity
   *
   * @return CurrencyInterface[]
   */
  public static function ownerEntityCurrencies(EntityOwnerInterface $entity = NULL) {
    return SELF::exchangeCurrencies(SELF::memberOf($entity, TRUE));
  }

  /**
   * Get all the currencies in the given exchanges
   *
   * @param array $exchange_ids
   *
   * @return CurrencyInterface[]
   */
  public static function exchangeCurrencies(array $exchanges) {
    $currencies = [];
    foreach ($exchanges as $exchange) {
      foreach ($exchange->get('currencies')->referencedEntities() as $currency) {
        $currencies[$currency->id()] = $currency;
      }
    }
    uasort($currencies, array('\Drupal\Component\Utility\SortArray', 'sortByWeightProperty'));
    return $currencies;
  }



  /**
   * get a list of all the currencies currently in a wallet's scope
   * which is to say, in any of the wallet's parent's exchanges
   *
   * @param UserInterface $account
   *
   * @return CurrencyInterface[]
   *   keyed by currency id
   *
   */
  public static function currenciesAvailableToUser(\Drupal\user\UserInterface $account = NULL) {
    $exchanges = $account->get(EXCHANGE_OG_FIELD)->referencedEntities();
    return Self::exchangeCurrencies($exchanges);
  }

  /**
   * Get all the currency ids for one or more exchanges
   * I don't think there is a way to do reverse lookup entity query.
   * So this is a bit of a hack, accessesing the entity reference field table directly
   * @param array $exchange_ids
   * @return array
   *   the currency ids
   *
   * @todo might it be better to iterate though the exchange entities and
   * $exchange->get('currencies')->referencedEntities()
   * then make them unique?
   * @todo put this a static container
   */
  static function getCurrenciesOfExchanges($exchange_ids) {
    //we need all the currencies referenced by these exchanges
    return \Drupal::database()->select('mcapi_exchange__currencies', 'c')
      ->fields('c', ['currencies_target_id'])
      ->condition('entity_id', $exchange_ids, 'IN')
      ->execute()->fetchCol();
  }

  /**
   * Get all the exchanges using a particular currency
   * I don't think there is a way to do reverse lookup entity query.
   * So this is a bit of a hack, accessesing the entity reference field table directly
   * @param string $curr_id
   *
   * @return array
   *   the exchange ids
   *
   */
  static function getExchangesUsing($curr_id) {
    return \Drupal::database()->select('mcapi_exchange__currencies', 'c')
      ->fields('c', ['entity_id'])
      ->condition('currencies_target_id', $curr_id)
      ->execute()->fetchCol();
  }


  static function exchangeLoadByCode($code) {
    $exchanges =  \Drupal::entityTypeManager()
      ->getStorage('mcapi_exchange')
      ->loadByProperties(['code' => $code]);
    return reset($exchanges);
  }

  /**
   * field api default value callback
   * Populate the currencies entityref field (on exchange entity)
   * using the currencies in exchanges the current user is in
   *
   * @param ContentEntityInterface $exchange
   *   the exchange
   *
   * @param array $field_definition
   *
   * @return string[]
   *   currency ids
   *
   * @todo this might not make sense usability wise;
   */
  static function defaultCurrencyId(ContentEntityInterface $exchange, $field_definition) {
    $output = [];
    //default currencies are the currencies of the exchanges of which the current user is a member
    if ($exchange_is = Exchanges::memberOf(NULL, TRUE)) {
      foreach (Exchanges::exchangeCurrencies($exchanges) as $currency) {
        $output[] = $currency->id();
      }
    }
    return $output;
  }

  /**
   * Utility:
   * get all the wallets whose holders are members of a given exchange
   * this is where things get ugly but we've saved having a redundant automated membership field on wallets
   * @param integer[] $exids
   *
   * @todo clarify and document whether this includes intertrading wallets. probably does
   */
  static function walletsInExchange(array $exids) {
    //get all the entities in the exchange
    $results = \Drupal::database()->select('og_membership', 'm')
      ->fields('m', ['member_entity_type', 'member_entity_id'])
      ->condition('group_entity_type', 'mcapi_exchange')
      ->condition('group_entity_id', $exids)
      ->execute()->fetchAll();
    foreach ($results as $result) {
      $holders[$result->member_entity_type][] = $result->member_entity_id;
    }
    return Mcapi::walletsOfHolders($holders);
  }
/*
 * [user] => Array
        (
            [1] => 70
            [2] => 23
            [3] => 30
            [4] => 42
            [5] => 46
            [6] => 40
        )
 */
}
