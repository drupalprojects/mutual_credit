<?php

/**
 * @file
 * Contains \Drupal\mcapi\Controller\WalletController.
 * 
 * @todo injections?
 *
 */

namespace Drupal\mcapi\Controller;

use Drupal\user\UserInterface;
use Drupal\mcapi\Entity\Wallet;

/**
 * Returns responses for Wallet routes.
 */
class WalletController {
  

  /**
   * The _content callback for the entity.mcapi_wallet.canonical route.
   * Provides a transaction history for the wallet
   *
   * This uses a saved view, but note that the view has no access control of itself.
   * Thats why it is done this way, using normal router entity access
   * 
   * @param UserInterface $user
   *
   * @return array
   *  a render array
   *
   * @todo check with the page display of this view to see whether the
   * route provided by views can include the views argument
   * If not, this workaround is just fine.
   */
  public function log(UserInterface $user) {
    //get this user's first wallet
    $wallet_ids = \Drupal::entityManager()
      ->getStorage('mcapi_wallet')
      ->filter(['holder' => $user]);
    $wid = reset($wallet_ids);
    return views_embed_view(
      'wallet_log',
      'embedded_in_route_mcapi_dot_wallet_log',
      $wid
      //could also pass the currency id and the year to the view, but from here we don't know which
    );
  }
  
  public function pageTitle(WalletInterface $wallet) {
    return $wallet->label();
  }

  public function userPageTitle(UserInterface $user) {
    $wallet_ids = \Drupal::entityManager()
      ->getStorage('mcapi_wallet')
      ->filter(['holder' => $user]);
    $wid = reset($wallet_ids);
    
    return Wallet::load($wid)->label();
  }

}
