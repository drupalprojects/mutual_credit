<?php

/**
 * @file
 * Contains \Drupal\mcapi\Controller\WalletController.
 */

namespace Drupal\mcapi\Controller;

use Drupal\Component\Utility\String;
use Drupal\Core\Controller\ControllerBase;
use Drupal\mcapi\Entity\WalletInterface;

/**
 * Returns responses for Wallet routes.
 */
class WalletController extends ControllerBase {

  /**
   * The _content callback for the mcapi.wallet_view route.
   * Provides a transaction history for the wallet
   *
   * This uses a saved view, but note that the view has no access control of itself.
   * Thats why it is done this way, using normal router entity access
   *
   * @return array
   *  An array suitable for drupal_render().
   *
   * @todo complete the views wallet access plugin (tricky)
   */
  public function page(WalletInterface $wallet) {
    drupal_set_message('In D8 alpha12 waiting for a fix to views.module->views_embed_view. See https://www.drupal.org/node/2208811#comment-8903685');
    drupal_set_message('In D8 alpha12 this view header fails to retrieve an entity when the integer id is given as a string.\n //to get around it, go to core/modules/views/plugin/views/area/Entity::render and put intval() round $entity_id');
    drupal_set_message("In D8 alpha12 views isn't picking up the path arguments. When it does, The wallet controller will be no longer needed");
    return views_embed_view('wallet_statement', 'embed_1', $wallet->id());
  }

}
