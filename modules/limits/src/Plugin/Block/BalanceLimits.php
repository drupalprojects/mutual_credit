<?php

namespace Drupal\mcapi_limits\Plugin\Block;

use Drupal\mcapi\Entity\Currency;
use Drupal\mcapi\Plugin\Block\McapiBlockBase;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a user balances block.
 *
 * @Block(
 *   id = "mcapi_limits",
 *   admin_label = @Translation("Balance limits"),
 *   category = @Translation("Community Accounting")
 * )
 */
class BalanceLimits extends McapiBlockBase {

  /**
   * {@inheritdoc}
   */
  public function access(AccountInterface $account, $return_as_object = FALSE) {
    $parent = parent::access();
    // Grant access IFF there are limits on the provided currencies.
    return $parent && TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form = parent::blockForm($form, $form_state);
    // Only show currencies which don't have the 'none' plugin.
    foreach ($form['curr_ids']['#options'] as $curr_id => $currname) {
      if (Currency::load($curr_id)->getThirdPartySetting('mcapi_limits', 'plugin', 'none') == 'none') {
        unset($form['curr_ids']['#options'][$curr_id]);
      }
    }
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    module_load_include('inc', 'mcapi_limits');
    // Where do we get the $wallet from?
    return mcapi_view_limits($wallet);

  }

}
