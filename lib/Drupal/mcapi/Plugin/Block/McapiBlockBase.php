<?php

namespace Drupal\mcapi\Plugin\Block;

use Drupal\block\BlockBase;
use Drupal\block\Annotation\Block;
use Drupal\Core\Annotation\Translation;
use Drupal\Core\Session\AccountInterface;

define('MCAPIBLOCK_USER_MODE_CURRENT', 1);
define('MCAPIBLOCK_USER_MODE_PROFILE', 0);

class McapiBlockBase extends BlockBase {

  protected $account;
  protected $currencies;

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return array(
      'currcodes' => array(),
      'user_source' => 0,
    );
  }

  /**
   * in profile mode, hide the block if we are not on a profile page
   */
  public function access(AccountInterface $account) {
    $request = \Drupal::request();;
    if($this->configuration['user_source'] == MCAPIBLOCK_USER_MODE_PROFILE) {
      if ($account = $request->attributes->get('user')) {
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
  public function blockForm($form, &$form_state) {
    parent::blockForm($form, $form_state);
    $form['currcodes'] = array(
      '#title' => t('Currencies'),
      '#description' => t('Select none to select all'),//there must be a string in Drupal that says that already?
      '#title_display' => 'before',
      '#type' => 'mcapi_currcodes',
      '#default_value' => $this->configuration['currcodes'],
      '#options' => mcapi_currency_list(TRUE),//ALL the currencies are listed
      '#multiple' => TRUE
    );
    $form['user_source'] = array(
      '#title' => t('User'),
      '#type' => 'radios',
      '#options' => array(
        MCAPIBLOCK_USER_MODE_PROFILE => t('Show as part of profile being viewed'),
        MCAPIBLOCK_USER_MODE_CURRENT => t('Show for logged in user')
      ),
      '#default_value' => $this->configuration['user_source']
    );
    return $form;
  }

  /**
   * Overrides \Drupal\block\BlockBase::blockSubmit().
   */
  public function blockSubmit($form, &$form_state) {
    parent::blockSubmit($form, $form_state);
    foreach ($form_state['values'] as $key => $val) {
      $this->configuration[$key] = $val;
    }
  }

  public function build() {
    $currencies = array_filter($this->configuration['currcodes']);
    //current user can only see the currencies which are in the same exchanges as him
    if (empty($currencies)) {
      //show only the currency which can be seen by the current user AND the profiled user
      $this->currencies = mcapi_get_available_currencies(
      	\Drupal::currentUser(),
        ($this->configuration['user_source'] == MCAPIBLOCK_USER_MODE_PROFILE) ? NULL : $this->account
      );
    }
    else{
      foreach ($currencies as $currcode) {
        $this->currencies[$currcode] = mcapi_currency_load($currcode);
      }
    }
  }

}

