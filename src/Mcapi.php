<?php

/**
 * @file
 *
 * Contains \Drupal\mcapi\Mcapi
 * utility methods
 *
 */

namespace Drupal\mcapi;

use Drupal\mcapi\Entity\Wallet;
use Drupal\Core\Entity\ContentEntityInterface;

class Mcapi {

  /**
   * Get a list of bundles which can have wallets
   * which is to say, whether it is a contentEntity configured to have at least 1 wallet
   *
   * @return array
   *   bundle names keyed by entity type ids
   *
   */
  static function walletableBundles($reset = FALSE) {
    $bundles = &drupal_static(__FUNCTION__);
    if (!is_array($bundles) || $reset) {
      if ($cache = \Drupal::cache()->get('walletableBundles')) {
        $bundles = $cache->data;
      }
      else{
        $bundles = [];
        foreach (\Drupal::Config('mcapi.settings')->get('entity_types') as $entity_bundle => $max) {
          if (!$max) {
            continue;
          }
          list($type, $bundle) = explode(':', $entity_bundle);
          $bundles[$type][$bundle] = $max;
        }
        \Drupal::cache()->set(
          'walletableBundles',
          $bundles,
          \Drupal\Core\Cache\CacheBackendInterface::CACHE_PERMANENT,
          ['walletable_bundles']//@todo what are the tags?
        );
      }
    }
    return $bundles;
  }

  /**
   * Find the maximum number of wallets a bundle can have
   *
   * @param type $entity_type_id
   * @param type $bundle
   * @return integer
   *   The maximum number of wallets
   */
  static function maxWalletsOfBundle($entity_type_id, $bundle) {
    $bundles = SELF::walletableBundles();
    if (isset($bundles[$entity_type_id])) {
      if (isset($bundles[$entity_type_id][$bundle])) {
        return $bundles[$entity_type_id][$bundle];
      }
    }
    return 0;
  }

  /**
   * utility function
   * loads any of the transaction operation actions
   */
  static function transactionActionLoad($operation) {
    //sometimes the $operation is from the url so it is shortened, and sometimes is the id of an action.
    //there is a convention that all transaction actions take the form transaction_ONEWORD
    if ($operation == 'operation') {//take the oneword from the given path
      $operation = \Drupal::routeMatch()->getParameter('operation');
    }
    if (substr($operation, 0, 12) != 'transaction_') {
      $action_name = 'transaction_'.$operation;
    }
    else {
      $action_name = $operation;
    }
    if ($action = \Drupal\system\Entity\Action::load($action_name)) {
      return $action;
    }
    throw new \Exception("No action with name '$operation'");
  }

  /**
   * utility function
   * load those special transaction operations which are also actions
   */
  static function transactionActionsLoad() {
    return \Drupal::entityTypeManager()
      ->getStorage('action')
      ->loadByProperties(['type' => 'mcapi_transaction']);
  }

  /**
   * uasort callback for configuration entities.
   * could be included in Drupal Core?
   */
  static function uasortWeight($a, $b) {
    $a_weight = (is_object($a) && property_exists($a, 'weight')) ? $a->weight : 0;
    $b_weight = (is_object($b) && property_exists($b, 'weight')) ? $b->weight : 0;
    if ($a_weight == $b_weight) {
      return 0;
    }
    return ($a_weight < $b_weight) ? -1 : 1;
  }


  /**
   * Utility function to populate a form widget's options with entity names
   *
   * @param string $entity_type_id
   *
   * @param array $data
   *   either entities of the given type, entity ids, or $conditions for entity_load_multiple_by_properties
   *
   * @return string[]
   *   The entity names, keyed by entity id
   *
   */
  static function entityLabelList($entity_type_id, array $data = []) {
    if (empty($data)) {
      $entities = \Drupal::entityTypeManager()->getStorage($entity_type_id)->loadMultiple();
    }
    elseif(is_string(key($data))) {
      $entities = entity_load_multiple_by_properties($entity_type_id, $data);
    }
    elseif(is_numeric(reset($data))) {
      $entities = \Drupal::entityTypeManager()->getStorage($entity_type_id)->loadMultiple($data);
    }
    else {
      $entities = $data;
    }
    if (property_exists(current($entities), 'weight') && count($entities) > 1) {
      uasort($entities, 'Mcapi::uasortWeight');
    }
    $list = [];
    foreach ($entities as $entity) {
      $list[$entity->id()] = $entity->label();
    }
    return $list;
  }

  /**
   * retrieve all the wallets held by a given ContentEntity
   *
   * @param ContentEntityInterface $entity
   * @param boolean $load
   *   TRUE means return the fully loaded wallets
   *
   * @return WalletInterface[]
   *   or just the wallet ids if $load is false
   */
  public static function walletsOf(ContentEntityInterface $entity, $load = FALSE) {
    $walletStorage = \Drupal::entityTypeManager()->getStorage('mcapi_wallet');
    $wids = $walletStorage->getQuery()
      ->condition('holder_entity_type', $entity->getEntityTypeId())
      ->condition('holder_entity_id', $entity->id())
      ->condition('payways', Wallet::PAYWAY_AUTO, '<>')
      ->execute();
    return $load ?
      $walletStorage->loadMultiple($wids) :
      $wids;
  }

  public static function walletsOfHolders(array $entities) {
    $query = \Drupal::database()->select('mcapi_wallet', 'w')
      ->fields('w', ['wid'])
      ->condition('orphaned', 0)
      ->condition('payways', Wallet::PAYWAY_AUTO, '<>');

    $or = $query->orConditionGroup();
    foreach ($entities as $entity_type_id => $entity_ids) {
      $and = $query->andConditionGroup()
        ->condition('holder_entity_type', $entity_type_id)
        ->condition('holder_entity_id', $entity_ids, 'IN');
      $or->condition($and);
    }
    return $query->condition($or)->execute()->fetchCol();
  }

  /**
   * Find out if the user can payin to at least one wallet and payout of at least one wallet
   *
   * @param integer $uid
   * @return boolean
   *   TRUE if the $uid has access to enough wallets to trade
   */
  public static function enoughWallets($uid) {
//dsm('enoughwallets '.$uid);
    $walletStorage = \Drupal::entityTypeManager()->getStorage('mcapi_wallet');
    $payin = $walletStorage->whichWalletsQuery('payin', $uid);
    $payout = $walletStorage->whichWalletsQuery('payout', $uid);
    if (count($payin) < 2 && count($payout) < 2) {
      //this is deliberately ambiguous as to whether you have no wallets or the system has no other wallets.
      drupal_set_message('There are no wallets for you to trade with', 'warning');
      return FALSE;
    }
    //there must be at least one wallet in each (and they must be different!)
    return (count(array_unique(array_merge($payin, $payout))) > 1);
  }


  /**
   * Service container
   * @return type
   */
  public static function transactionRelatives() {
    return \Drupal::service('mcapi.transaction_relative_manager');
  }


  /**
   * {@inheritdoc}
   * @todotim make a new baseField in this file, a boolean called 'used' default 0
   * in getSummaries set that flag and save
   * in this function just read that flag
   */
  public static function walletIsUsed($wid) {
    drupal_set_message('Checking wallet isUsed()'); //is this called too often?
    $count = \Drupal::entityTypeManager()
      ->getStorage('mcapi_transaction')->getQuery()
      ->condition('involving', $wid)
      ->count()
      ->execute();
  }

  public static function twigHelp(array $exclude = []) {
    foreach (array_keys(\Drupal::Token()->getInfo()['tokens']['xaction']) as $token) {
      $tokens[] = "[xaction:$token]";
    }
    $tokens = array_diff(
      array_keys($all_fields),
      $exclude
    );
    return ' {{ ' .
      implode(' }}, {{ ', $tokens) .
      ' }} ' .
      \Drupal::l(t('What is twig?'), \Drupal\Core\Url::fromUri('http://twig.sensiolabs.org/doc/templates.html'));
  }

}