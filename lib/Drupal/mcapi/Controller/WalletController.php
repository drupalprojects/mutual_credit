<?php

/**
 * @file
 * Contains \Drupal\mcapi\Controller\WalletController.
 */

namespace Drupal\mcapi\Controller;

use Drupal\Component\Utility\String;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityInterface;

/**
 * Returns responses for Wallet routes.
 */
class WalletController extends ControllerBase {

  /**
   * The _content callback for the mcapi.wallet_view route.
   *
   * This uses a saved view, but note that the view has no access control of itself.
   * Thats why it is done this way, using normal router entity access
   * @todo complete the views wallet access plugin (tricky)
   *
   * @param Drupal\mcapi\EntityInterface $wallet
   *
   * @return array
   *  An array suitable for drupal_render().
   */
  public function page(EntityInterface $mcapi_wallet) {
    return views_embed_view('wallet_statement', 'embed_1', $mcapi_wallet->id());
  }

  /**
   * The _title_callback for the mcapi.wallet_view route.
   *
   * @param EntityInterface $wallet
   *
   * @return string
   *   The page title.
   */
  public function pageTitle(EntityInterface $mcapi_wallet) {
    return String::checkPlain($mcapi_wallet->label()));
  }

}
