<?php

/**
 * @file
 * Contains \Drupal\mcapi\Mcapi.
 */

namespace Drupal\mcapi;

use Drupal\user\Entity\User;
use Drupal\mcapi\Exchange;
use Drupal\mcapi\Entity\Currency;
use Drupal\mcapi\Entity\State;
use Drupal\mcapi_exchanges\Entity\Exchanges;

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
    $bundles = Exchange::walletableBundles();
    return in_array($type_id,  $bundles) and in_array($bundles[$type_id], $entity->bundle());
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
  public static function processTransactionVars(array &$vars, TransactionInterface $transaction) {
    $xid = $transaction->xid->value;
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
   * Check if an entity is the owner of a wallet
   * @todo this is really a constant, but constants can't store arrays. What @todo?
   *
   * @return array
   *   THE list of ops because arrays cannot be stored in constants
   * 
   * @todo this needs to be a plugin, or at least alterable by the exchanges module
   */
  public static function walletOps() {
    return [
      'details' => t('View transaction log'), 
      'summary' => t('View summary'), 
      'payin' => t('Pay into this wallet'),
      'payout' => t('Pay out of this wallet'),
    ];
  }
  
  
}
