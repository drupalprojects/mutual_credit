<?php

namespace Drupal\mcapi\Plugin\Block;

/**
 * @file
 * Contains \Drupal\mcapi\Plugin\Block\Balances
 */

use Drupal\mcapi\Plugin\Block\McapiBlockBase;


/**
 * Provides a user balances block.
 *
 * @Block(
 *   id = "mcapi_user_summary",
 *   admin_label = @Translation("Balances"),
 *   category = @Translation("Community Accounting")
 * )
 */
class UserSummary extends McapiBlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    parent::build();
    module_load_include('inc', 'mcapi');
    return mcapi_view_user_summary($this->account, $this->currencies);
  }
}
