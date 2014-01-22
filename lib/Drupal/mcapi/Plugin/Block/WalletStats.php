<?php

namespace Drupal\mcapi\Plugin\Block;

/**
 * @file
 * Contains \Drupal\mcapi\Plugin\Block\WalletStats
 */

use Drupal\mcapi\Plugin\Block\McapiBlockBase;


/**
 * Displays some stats for a wallet.
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
    module_load_include('inc', 'mcapi');
    //@todo make this work
    $build = mcapi_view_wallet_summary($this->account->wallets, $this->currencies);
    //this is helpful for when the signatures module wants to alter the block.
    $build['#account'] = $this->account;
    return $build;
  }
}
