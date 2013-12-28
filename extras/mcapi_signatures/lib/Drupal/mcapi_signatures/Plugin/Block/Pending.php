<?php

namespace Drupal\mcapi_signatures\Plugin\Block;

/**
 * @file
 * Contains \Drupal\mcapi\Plugin\Block\Pending
 */

use Drupal\mcapi\Plugin\Block\McapiBlockBase;


/**
 * Provides a user balances block.
 *
 * @Block(
 *   id = "mcapi_pending",
 *   admin_label = @Translation("Pending transactions"),
 *   category = @Translation("Community Accounting")
 * )
 */
class UserSummary extends McapiBlockBase {

  public function defaultConfiguration() {
    $conf = parent::defaultConfiguration();
    return $conf += array('mode' => 'waiting_on_uid');
  }

  public function blockForm($form, &$form_state) {
    $form = parent::blockForm($form, $form_state);
    $form['mode'] = array(
      '#title' => t('Transactions to show'),
      '#type' => 'radios',
      '#options' => array(//these array keys relate to d7 blocks
        'user_pending' => t('All pending transactions (per user)'),
        'waiting_on_uid' => t('Signatures for user to sign'),
        'uid_waiting_on' => t('User is waiting for these signatures')
      ),
      '#default_value' => $this->configuration['mode'],
      '#required' => TRUE
    );
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    parent::build();//this gets the config ready
    module_load_include('inc', 'mcapi_signatures');
    //the block title is 'Pending', but we'll clarify in a subheading
    switch ($this->configuration['mode']) {
    	case 'pending_sorted':
    	  $renderable[] = list_pending_for_uid($this->account->id(), $this->currencies, TRUE);
    	  break;
    	case 'pending_unsorted':
    	  $renderable[] = list_pending_for_uid($this->account->id(), $this->currencies, FALSE);
    	  break;
    	case 'waiting_on_uid':
    	  $renderable[] = array('#markup' => t('Awaiting my signature'));
    	  $renderable[] = list_waiting_on_uid($this->account->id(), $this->currencies);
    	  break;
    	case 'uid_waiting_on':
    	  $renderable[] = array('#markup' => t('Awaiting another signature'));
    	  $renderable[] = list_waiting_not_on_uid($this->account->id(), $this->currencies);
    	  break;
    }
    return $renderable;
  }
}
