<?php

/**
 * @file
 * Contains \Drupal\mcapi\Plugin\Block\BalanceLimits
 */

namespace Drupal\mcapi_limits\Plugin\Block;

use Drupal\mcapi\Plugin\Block\McapiBlockBase;
use Drupal\Core\Session\AccountInterface;


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

  public function access(AccountInterface $account) {
    $parent = parent::access();
    return $parent && TRUE;//grant access IFF there are limits on the provided currencies
  }

  public function defaultConfiguration() {
    $conf = parent::defaultConfiguration();
    $conf['absolute'] = 'absolute';
    return $conf;
  }

  /**
   * Overrides \Drupal\block\BlockBase::blockForm().
   */
  public function blockForm($form, $form_state) {
    $form = parent::blockForm($form, $form_state);
    //reduce the list of currencies to those which don't have the 'none' plugin
    foreach ($form['curr_ids']['#options'] as $curr_id => $currname) {
      if (mcapi_currency_load($curr_id)->limits_plugin == 'none') {
        unset($form['curr_ids']['#options'][$curr_id]);
      }
    }
    $form['absolute'] = array(
      '#title' => t('Range'),
      '#type' => 'radios',
      '#options' => array(
        'absolute' => t('Show min, max and current balance'),
        'relative' => t('Show limits for earning and spending, relative to balance'),
      ),
      '#default_value' => $this->configuration['absolute'],
      '#weight' => '5'
    );
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    parent::build();
    return mcapi_view_limits(
      $this->account,
      $this->currencies,
      $this->configuration['absolute'] == 'absolute'
    );

  }
}