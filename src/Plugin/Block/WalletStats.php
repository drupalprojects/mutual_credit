<?php

/**
 * @file
 * Contains \Drupal\mcapi\Plugin\Block\WalletStats
 * @todo inject entityTypeManager? Find the wallet to use or remove this block!
 */

namespace Drupal\mcapi\Plugin\Block;

use Drupal\mcapi\Plugin\Block\McapiBlockBase;
use Drupal\mcapi\Mcapi;

/**
 * Displays balances for all the wallets belonging to an entity
 *
 * @Block(
 *   id = "mcapi_wallet_stats",
 *   admin_label = @Translation("Wallets trading summary"),
 *   category = @Translation("Community Accounting")
 * )
 */
class WalletStats extends McapiBlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    
    //@todo where do we get the entity from?
    
    return [
      '#theme' => 'mcapi_wallets',
      '#wallets' => Mcapi::walletsOf($content____entity)
    ];
  }
}
