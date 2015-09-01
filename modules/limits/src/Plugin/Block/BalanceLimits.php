<?php

/**
 * @file
 * Contains \Drupal\mcapi\Plugin\Block\BalanceLimits
 *
 * @todo how to get the wallet or wallets from the block context?
 * //from the entity being viewed? or how else?
 */

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

  public function access(AccountInterface $account, $return_as_object = false) {
    $parent = parent::access();
    return $parent && TRUE;//grant access IFF there are limits on the provided currencies
  }

  /**
   * Overrides \Drupal\block\BlockBase::blockForm().
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form = parent::blockForm($form, $form_state);
    //reduce the list of currencies to those which don't have the 'none' plugin
    foreach ($form['curr_ids']['#options'] as $curr_id => $currname) {
      if (Currency::load($curr_id)->getThirdPartySetting('mcapi_limits', 'plugin', 'none') == 'none') {
        unset($form['curr_ids']['#options'][$curr_id]);
      }
    }
    /** now the perspective is a property of the currency and the limits, not user 1 choice.
    $form['perspective'] = [
      '#title' => $this->t('Perspective'),
      '#type' => 'radios',
      '#options' => [
        'absolute' => $this->t('View the absolute limits with the balance between.'),
        'relative' => $this->t('View the earning and spending limits relative to the current balance')
      ],
      '#default_value' => $this->configuration['perspective']
    ];
     * 
     */
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    module_load_include('inc', 'mcapi_limits');
    //where do we get the $wallet from?
    return mcapi_view_limits($wallet);

  }
}