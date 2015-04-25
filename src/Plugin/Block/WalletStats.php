<?php

namespace Drupal\mcapi\Plugin\Block;

/**
 * @file
 * Contains \Drupal\mcapi\Plugin\Block\WalletStats
 */

use Drupal\mcapi\Plugin\Block\McapiBlockBase;


/**
 * Displays balances for all the wallets belonging to an entity
 *
 * @Block(
 *   id = "mcapi_wallet_stats",
 *   admin_label = @Translation("User trading summary"),
 *   category = @Translation("Community Accounting")
 * )
 */
class WalletStats extends McapiBlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    parent::build();
    return [
      '#theme' => 'mcapi_wallets',
      '#wallets' => $this->account->wallets
    ];
  }
}
