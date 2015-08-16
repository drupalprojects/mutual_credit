<?php

namespace Drupal\mcapi\Plugin\Block;

/**
 * @file
 * Contains \Drupal\mcapi\Plugin\Block\WalletStats
 * @todo inject entityManager?
 */

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
      '#wallets' => \Drupal::entityManager()
        ->getStorage('mcapi_wallet')
        ->filter(
          ['holder' => $contentEntity]
        )
    ];
  }
}
