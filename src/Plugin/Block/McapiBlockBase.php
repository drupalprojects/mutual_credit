<?php

namespace Drupal\mcapi\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\mcapi\Entity\Currency;
use Drupal\mcapi\Exchange;

define('MCAPIBLOCK_USER_MODE_CURRENT', 1);
define('MCAPIBLOCK_USER_MODE_PROFILE', 0);

class McapiBlockBase extends BlockBase {

  protected $account;
  protected $currencies;

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'curr_ids' => [],
      'user_source' => 0,
    ];
  }

  /**
   * in profile mode, hide the block if we are not on a profile page
   */
  public function access(AccountInterface $account, $return_as_object = false) {
    $account = \Drupal::routeMatch()->getParameter('user');
    //don't we need to call the parent?  See blockbase::access after alpha14
    debug($this->getPluginDefinition(), 'check that block access is working');
    if($this->configuration['user_source'] == MCAPIBLOCK_USER_MODE_PROFILE) {
      if ($account = \Drupal::request()->attributes->get('user')) {
        $this->account = $account;
      }
      else return FALSE;
    }
    else $this->account = \Drupal::currentUser();

    return TRUE;
  }


  /**
   * Overrides \Drupal\block\BlockBase::blockForm().
   */
  public function blockForm($form, FormStateInterface $form_state) {
    parent::blockForm($form, $form_state);
    $form['curr_ids'] = [
      '#title' => t('Currencies'),
      '#description' => t('Select none to select all'),//there must be a string in Drupal that says that already?
      '#title_display' => 'before',
      '#type' => 'mcapi_currency_select',
      '#default_value' => $this->configuration['curr_ids'],
      '#options' => mcapi_entity_label_list('mcapi_currency', array('status' => TRUE)),
      '#multiple' => TRUE
    ];
    $form['user_source'] = [
      '#title' => t('User'),
      '#type' => 'radios',
      '#options' => array(
        MCAPIBLOCK_USER_MODE_PROFILE => t('Show as part of profile being viewed'),
        MCAPIBLOCK_USER_MODE_CURRENT => t('Show for logged in user')
      ),
      '#default_value' => $this->configuration['user_source']
    ];
    return $form;
  }

  /**
   * Overrides \Drupal\block\BlockBase::blockSubmit().
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    parent::blockSubmit($form, $form_state);
    $values = $form_state->getValues();
    foreach ($values as $key => $val) {
      $this->configuration[$key] = $val;
    }
  }

  public function build() {
    $curr_ids = array_filter($this->configuration['curr_ids']);
    //might want to move this to mcapi_exchanges
    if (empty($curr_ids)) {
      $otheruser = ($this->configuration['user_source'] == MCAPIBLOCK_USER_MODE_PROFILE) ? NULL : $this->account;
      //show only the currency which can be seen by the current user AND the profiled user
      $this->currencies = array_intersect_key(
      	Exchange::ownerEntityCurrencies(\Drupal::currentUser()),
        Exchange::ownerEntityCurrencies($otheruser)
      );
    }
    else{
      foreach ($currencies as $curr_id) {
        $this->currencies[$curr_id] = mcapi_currency_load($curr_id);
      }
    }
  }

}

