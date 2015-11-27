<?php

/**
 * @file
 * Contains \Drupal\mcapi\Plugin\Block\WalletStats
 * @todo inject entityTypeManager?
 */

namespace Drupal\mcapi\Plugin\Block;

use Drupal\mcapi\Plugin\Block\McapiBlockBase;

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
      '#wallets' => \Drupal::entityTypeManager()
        ->getStorage('mcapi_wallet')
        ->filter(
          ['holder' => $contentEntity]
        )
    ];
  }
}
