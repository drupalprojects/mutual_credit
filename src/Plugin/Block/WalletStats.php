<?php

namespace Drupal\mcapi\Plugin\Block;

/**
 * Displays balances for all the wallets belonging to an entity.
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
    parent::build();
    // @todo where do we get the entity from?
    return [
      '#theme' => 'mcapi_wallets',
      '#wallets' => $this->entityTypeManager->getStorage('mcapi_wallet')->walletsOf($this->account),
    ];
  }

}
