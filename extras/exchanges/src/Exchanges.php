<?php

/**
 * @file
 * 
 * Contains \Drupal\mcapi_exchanges\Exchanges.
 * Replaces \Drupal\mcapi\Exchanges
 * 
 */

namespace Drupal\mcapi_exchanges;

use Drupal\user\Entity\User;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\mcapi_exchanges\Entity\Exchange;
use Drupal\mcapi\Entity\Currency;
use Drupal\mcapi\Entity\Wallet;


class Exchanges {
  
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
    if (!is_object($entity)) {
      $entity = User::load(\Drupal::currentUser()->id());
    }
    if ($entity->getEntityTypeId() == 'user') drupal_set_message('Exchanges::in not suitable for finding out what group a user is in');

//    $groups = og_get_entity_groups($entity_type = 'mcapi_exchange', $entity, array(OG_STATE_ACTIVE), EXCHANGE_OG_REF);
//    return $groups['mcapi_exchange'];

    $exchanges = [];
    if (is_null($entity)) {
      $entity = User::load(\Drupal::currentUser()->id());
    }
    if ($entity->getEntityTypeId() == 'mcapi_exchange') {
      //an exchange references itself only
      $exchanges[] = $entity->id();
    }
    else{
      if (in_array($entity->getEntityTypeId(), Exchange::walletableBundles())) {
        foreach($entity->{EXCHANGE_OG_REF}->referencedEntities() as $entity) {
          //exclude disabled exchangesloadby
          if (($enabled && !$entity->status->value) || ($open && !$entity->open->value)) {
            continue;
          }
          $exchanges[] = $entity->id();
        }
      }
    }
    return $exchanges;
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
   * @deprecated replace with og_get_entity_groups($entity_type = 'user', $entity = NULL, $states = array(OG_STATE_ACTIVE), $field_name = NULL);
   */
  public static function walletInExchanges(Wallet $wallet, $open = FALSE) {
    return $wallet->entity_type == 'mcapi_exchange' ?
      array($wallet->pid => $wallet->getOwner()) ://TODO how do exchanges own wallets if exchanges aren't an entity?
      Self::in($wallet->getOwner(), TRUE, $open);
  }
  
  
  //walletable bundles are any bundles with the exchange field on them
  public static function walletableBundles() {
    debug('Walletable bundles override may not be needed');
    $field_defs = \Drupal::entityManager()
      ->getStorage('field_config')
      ->loadbyProperties(array('field_name' => EXCHANGE_OG_REF));
    foreach ($field_defs as $field) {
      $types[$field->entity_type][$field->bundle];
    }
    return $types;
  }

  
  /*
   * identify a new parent entity for a wallet
   */
  public static function new_owner($owner) {
    if($exchange_ids = Exchanges::in($owner, FALSE)) {
      //if the parent entity was in more than one exchange, this will pick a random one to take ownership
      return Exchange::load(reset($exchanges));

    }
  }
  
  public static function currencies(array $exchange_ids, $ticks = FALSE) {
    $currencies = [];
    foreach (Exchange::loadmultiple($exchange_ids) as $exchange) {
      foreach ($exchange->get('currencies')->referencedEntities() as $currency) {
        if (!$ticks || $currency->ticks) {
          $currencies[$currency->id()] = $currency;
        }
      }
    }
    uasort($currencies, array('\Drupal\Component\Utility\SortArray', 'sortByWeightProperty'));
    return $currencies;
  }
  
}
