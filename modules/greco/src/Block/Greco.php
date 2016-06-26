<?php

namespace Drupal\mcapi\Plugin\Block;

/**
 * Provides a user balances block.
 *
 * @Block(
 *   id = "greco",
 *   admin_label = @Translation("Greco index over time"),
 *   category = @Translation("Community Accounting")
 * )
 *
 * @todo: How do we calculate the block title?
 */
class Greco extends McapiBlockBase {

  /**
   * This needs to be singular or plural, for example.
   */
  public function getTitle() {
    return $this->label();
  }

  /**
   * {@inheritdoc}
   * 
   * return a render array.
   */
  public function build() {
    return mcapi_greco_gchart();
  }

}
