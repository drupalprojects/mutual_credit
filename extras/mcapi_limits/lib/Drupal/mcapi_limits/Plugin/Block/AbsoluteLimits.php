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
 *   id = "mcapi_limits_absolute",
 *   admin_label = @Translation("Absolute limits"),
 *   category = @Translation("Community Accounting")
 * )
 */
class AbsoluteLimits extends McapiBlockBase {

  public function access() {
    drupal_set_message('checking access for absolute limits block');
    if (parent::access()) {
      return TRUE;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function build() {

    drupal_set_message('building absolute limits block');
    parent::build();
    $renderable = array();

    $currcodes = $this->configuration['currcodes'];
    foreach ($this->configuration['currcodes'] as $currcode) {
      $currency = mcapi_currency_load($currcode);
      if (\Drupal::currentUser()->id() != $this->configuration['account']->id() && !$currency->currency_access('trader_data', $this->configuration['account'])) continue;
      $limits = $this->calc($currency, $account);
      $renderable[$currcode] = array(
        '#theme' => 'trading_limits',
        '#currcode' => $currcode,
        '#uid' => $uid
      );
      if (isset($limits['spend_limit'])) {
        $renderable[$currcode]['#spend_limit'] = $limits['spend_limit'];
      }
      if (isset($limits['earn_limit'])) {
        $renderable[$currcode]['#earn_limit'] = $limits['earn_limit'];
      }
    }
    return $renderable;
  }
}
