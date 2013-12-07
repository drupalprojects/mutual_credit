<?php

namespace Drupal\mcapi\Plugin\Block;

use Drupal\block\BlockBase;
use Drupal\block\Annotation\Block;
use Drupal\Core\Annotation\Translation;
use Drupal\Core\Session\AccountInterface;

define('MCAPIBLOCK_USER_MODE_CURRENT', 1);
define('MCAPIBLOCK_USER_MODE_PROFILE', 0);

class McapiBlockBase extends BlockBase {

  var $account;
  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    $conf = parent::defaultConfiguration();
    return $conf += array(
      'currcodes' => array(),
      'user_source' => 0,
    );
  }

  /**
   * in profile mode, hide the block if we are not on a profile page
   */
  public function access(AccountInterface $account) {
    if($this->configuration['user_source'] == MCAPIBLOCK_USER_MODE_PROFILE) {
      return arg(0) == 'user';
    }
    return TRUE;
  }


  /**
   * Overrides \Drupal\block\BlockBase::blockForm().
   */
  public function blockForm($form, &$form_state) {
    parent::blockForm($form, $form_state);
    $form['currcodes'] = array(
      '#title' => t('Currencies'),
      '#title_display' => 'before',
      '#type' => 'mcapi_currcodes',
      '#default_value' => $this->configuration['currcodes'],
      '#options' => 'all'
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
    $this->configuration['currcodes'] = $form_state['values']['currcodes'];
    $this->configuration['user_source'] = $form_state['values']['user_source'];
  }

  public function build() {
    if ($this->configuration['user_source'] == MCAPIBLOCK_USER_MODE_CURRENT )
      $this->account = \Drupal::currentUser();
    else {
      //TODO how do we get the second argument
      $this->account = user_load(arg(1));
    }
  }

}
