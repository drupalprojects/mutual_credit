<?php

/**
 * @file
 * Contains \Drupal\mcapi\Mcapi.
 */

namespace Drupal\mcapi;

use Drupal\user\Entity\User;
use Drupal\mcapi\Exchanges;
use Drupal\mcapi\Entity\Currency;
use Drupal\mcapi\Entity\State;
use Drupal\mcapi_exchanges\Entity\Exchange;

/**
 * 
 * 
 */
class Mcapi {
  
  /**
   * Determine whether the system architecture allows an entity to hold wallets.
   * which is to say, whether it is a contentEntity with an audience field 
   * 
   * @param unknown $entity_type
   *   an entity OR entityType object
   *
   * @param string $bundle
   *   bundlename, if the first arg is an entityType
   *
   * @return NULL  | string
   *   TRUE means the entitytype can hold wallets
   * 
   * @todo update function and documentation after OG is integrated
   */
  public static function walletable($entity) {
    $type_id = $entity->getEntityTypeId();
    if ($type_id == 'user') return TRUE; //we might not need to ask this
    if ($type_id == 'mcapi_exchange') return TRUE; //because of intertrading wallets
    $bundles = Self::walletableBundles();
    return in_array($type_id,  $bundles) && in_array($bundles[$type_id], $entity->bundle());
  }
  
  /**
   * Get a list of entities
   * which is to say, whether it is a contentEntity with an audience field 
   * 
   * @param unknown $entity_type
   *   an entity OR entityType object
   *
   * @param string $bundle
   *   bundlename, if the first arg is an entityType
   *
   * @return NULL  | string
   *   TRUE means the entitytype can hold wallets
   * 
   * @todo update function and documentation after OG is integrated
   */
  public static function walletableBundles() {
    $types = &drupal_static('walletableBundles');
    if (!$types) {
      if (0 && $cache = \Drupal::cache()->get('walletableBundles')) {
        $types = $cache->data;
      }
      else{
        if (\Drupal::moduleHandler()->moduleExists('mcapi_exchange')) {
          $field_defs = \Drupal::entityManager()
            ->getStorage('field_config')
            ->loadbyProperties(array('field_name' => EXCHANGE_OG_REF));
          foreach ($field_defs as $field) {
            $types[$field->entity_type][$field->bundle];
          }
        }
        else {
          foreach (array_keys(array_filter(\Drupal::Config('mcapi.wallets')->get('entity_types'))) as $entity_bundle) {
            list($entity, $bundle) = explode(':', $entity_bundle);
            $types[$entity][] = $bundle;
          }
        }
        \Drupal::cache()->set(
          'walletableBundles',
          $types,
          \Drupal\Core\Cache\CacheBackendInterface::CACHE_PERMANENT,
          []//TODO what cache tags to use if the cache is permanent?
        );
      }
    }
    return $types;
  }
  
  /**
  * Delete all transaction entities
  * For development only!
   * 
  * @todo move this to be a drush command
  */
  public static function mcapi_wipeslate() {
    $conf = \Drupal::config('mcapi.misc');
    $storage = \Drupal::entityManager()->getStorage('mcapi_transaction');
    $entities = $storage->loadMultiple();
    $storage->delete($entities);
    echo 'all transactions deleted';
  }

  /**
   * {@inheritdoc}
   */
  public static function transactionTokens($include_virtual = FALSE) {
    $tokens = \Drupal::entityManager()->getFieldDefinitions('mcapi_transaction', 'mcapi_transaction');
    //looks like fieldmap isn't needed at all, which will simplify this function
    unset($tokens['uuid'], $tokens['xid'], $tokens['parent'], $tokens['type'], $tokens['children']);
    $tokens = array_keys($tokens);

    if ($include_virtual){
      $tokens[] = 'url';
    }
    return $tokens;
  }

  /**
   * {@inheritdoc}
   */
  public static function processTransactionVars(array &$vars, TransactionInterface $transaction) {
    $xid = $transaction->get('xid')->value;
    $v['state'] = State::load($transaction->type->entity->start_state)->label;
    $v['type'] = $transaction->type->entity->label;
    $v['serial'] = $transaction->serial->value;
    foreach (['payer', 'payee'] as $trader) {
      $owner = $transaction->{$trader}->entity->getOwner();
      if ($owner->hasLinkTemplate('canonical')) {
        $v[$trader] = $owner->link();
      }
      //if there is no canonical link to this entity just link to the wallet itself
      else {
        $v[$trader] = $transaction->{$trader}->entity->link();
      }
    }
    $v['creator'] = $transaction->creator->entity->link();
    //TODO do we need to sanitise this or does the theme engine do it?
    $v['description'] = $transaction->description->getString();

    //NB the transaction certificate, which uses entity_view_display overwrites field with display options, i.e. this one!
    //but this is needed for the sentence display
    $v['worth'] = $transaction->worth->view();

    //TODO LEAVE THIS TILL AFTER alpha 11
    $v['created'] = format_date($transaction->created->value, 'medium');

    //the token service lets you pass url options, but this function doesn't
    $v['url'] = $transaction->url('canonical');
    $vars += $v;
  }
  
 /**
  * Get all the currencies from (common to) the given exchanges, sorted
  * 
  * @param array $exchange_ids
  *
  * @param boolean $ticks
  *   filter out currencies with no exchange rate
  *
  * @param boolean $status
  *   filter out disabled currencies
  *
  * @return Currency[]
  *   the intersection of used currencies
  */
  public static function currencies(array $exchange_ids, $ticks = FALSE) {
    if (\Drupal::moduleHandler()->moduleExists('mcapi_exchanges')) {
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
    //All currencies are available
    else return Currency::loadMultiple();
  }
  
}
