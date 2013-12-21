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
 *   id = "mcapi_absolute_limits",
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
  public function blockForm($form, &$form_state) {
    $form = parent::blockForm($form, $form_state);
    //reduce the list of currencies to those which don't have the 'none' plugin
    foreach ($form['currcodes']['#options'] as $currcode => $currname) {
      if (mcapi_currency_load($currcode)->limits_plugin == 'none') {
        unset($form['currcodes']['#options'][$currcode]);
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
    //get a render array for each applicable currency
    $manager = \Drupal::service('plugin.manager.mcapi_limits');
    $renderable = array();
    foreach ($this->currencies as $currency) {
      if ($currency->limits_plugin == 'none') continue;
      if ($this->configuration['absolute'] == 'absolute') {
        $renderable[$currency->id()] = $manager->setup($currency)->view($this->account);
      }
      else {
        $renderable[$currency->id()] = array(
          '#theme' => 'mcapi_limits_relative',
          '#account' => $this->account,
          '#currency' => $currency,
        );
      }
    }
    return $renderable;
  }
}
