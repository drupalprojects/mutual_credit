<?php

/**
 * @file
 * Definition of Drupal\mcapi\WalletViewBuilder.
 */

namespace Drupal\mcapi;

use Drupal\Core\Entity\Display\EntityViewDisplayInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityViewBuilder;

/**
 * Render controller for nodes.
 */
class WalletViewBuilder extends EntityViewBuilder {

  /**
   * {@inheritdoc}
   */
  //@todo currently this doesn't work because of a bug in alpha7
  //which fails to return an entity when the integer id is given as a string.
  //to get around it, go to Drupal\views\Plugin\views\area\Entity::render and intval($entity_id)
  public function buildContent(array $entities, array $displays, $view_mode, $langcode = NULL) {

    parent::buildContent($entities, $displays, $view_mode, $langcode);
    $storage = \Drupal::entityManager()->getStorage('mcapi_transaction');

    //we're just sticking all these in a render array, but a theme callback would be much nicer
    //guess we have to choose whether we want to play with Twig or Drupal's display modes
    foreach ($entities as $wallet) {
      /* not sure if we should be using these
      $bundle = $wallet->bundle();
      $display = $displays[$bundle];
      */
      $wallet->content['wallets_stats'] = array(
        '#theme' => 'wallet_stats',//this renders as css block (table) so doesn't sit well with the histories
        '#wallet' => $wallet,
        '#weight' => 5
      );
      // the following are then managed by the entity_display
      $owner = $wallet->getOwner();
      $wallet->content['owner'] = array(
        //todo make a title or heading for this
        '#markup' => l($owner->label(), $owner->url())//assumes the canonical
      );
      $wallet->content['wallet_balance'] = show_wallet_summaries($wallet);
      $wallet->content['wallet_history'] = show_wallet_histories($wallet);
      $wallet->content['wallet_balance_bars'] = show_wallet_balance_bars($wallet);

      /* these pi charts will have to go into an alter hook
       *  because categories aren't actually part of this module.
       *  hook_mcapi_wallet_view_alter().
        $entity->content['incoming_categories'] = array();
        $entity->content['outgoing_categories'] = array();
      */
    }
  }


}
