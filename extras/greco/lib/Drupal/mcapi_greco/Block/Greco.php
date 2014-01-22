<?php

namespace Drupal\mcapi\Plugin\Block;

/**
 * @file
 * Contains \Drupal\mcapi\Plugin\Block\BalanceHistory
 */

use Drupal\mcapi\Plugin\Block\McapiBlockBase;


/**
 * Provides a user balances block.
 *
 * @Block(
 *   id = "greco",
 *   admin_label = @Translation("Greco index over time"),
 *   category = @Translation("Community Accounting")
 * )
 */
class Greco extends McapiBlockBase {

  //TODO: How do we calculate the block title?
  //This needs to be singular or plural, for example
  public function getTitle() {
    return $this->label();
  }

  /**
   * {@inheritdoc}
   * return a render array
   */
  public function build() {
    return mcapi_greco_gchart();
  }
}

