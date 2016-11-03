<?php

namespace Drupal\mcapi\Plugin\Block;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\mcapi\Exchange;
use Drupal\mcapi\Mcapi;
use Drupal\mcapi\Currency;

/**
 * Base block class for user transaction data.
 */
class McapiBlockBase extends BlockBase {

  const USER_MODE_CURRENT = 1;
  const USER_MODE_PROFILE = 0;

  protected $account;
  protected $currencies;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    //mtrace();//inject routeMatch & currentuser
  }


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
   * {@inheritdoc}
   *
   * In profile mode, hide the block if we are not on a profile page.
   */
  public function blockAccess(AccountInterface $account) {
    // don't we need to call the parent?  See blockbase::access after alpha14.
    debug($this->getPluginDefinition(), 'check that block access is working');
    if ($this->configuration['user_source'] == static::USER_MODE_PROFILE) {
      if (!\Drupal::routeMatch()->getParameters()->has('user')) {
        return AccessResult::forbidden();
      }
    }
    return AccessResult::allowed();
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    parent::blockForm($form, $form_state);
    $form['curr_ids'] = [
      '#title' => t('Currencies'),
    // There must be a string in Drupal that says that already?
      '#description' => t('Select none to select all'),
      '#title_display' => 'before',
      '#type' => 'mcapi_currency_select',
      '#default_value' => $this->configuration['curr_ids'],
      '#options' => Mcapi::entityLabelList('mcapi_currency', array('status' => TRUE)),
      '#multiple' => TRUE,
    ];
    $form['user_source'] = [
      '#title' => t('User'),
      '#type' => 'radios',
      '#options' => array(
        static::USER_MODE_PROFILE => t('Show as part of profile being viewed'),
        static::USER_MODE_CURRENT => t('Show for logged in user'),
      ),
      '#default_value' => $this->configuration['user_source'],
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    parent::blockSubmit($form, $form_state);
    $values = $form_state->getValues();
    foreach ($values as $key => $val) {
      $this->configuration[$key] = $val;
    }
     $this->configuration['curr_ids'] = array_filter($this->configuration['curr_ids']);
  }

  /**
   * {@inheritdoc}
   */
  public function build() {

    if ($this->configuration['user_source'] == static::USER_MODE_PROFILE) {
      // We already know the parameter bag has 'user' from blockAccess
      $this->account = \Drupal::routeMatch()->getParameter('user');
    }
    else {// Current user
      $this->account = \Drupal::currentUser();
    }

    // Might want to move this to mcapi_exchanges.
    if (empty($this->configuration['curr_ids'])) {
      $this->currencies = Exchange::currenciesAvailableToUser($this->account);
    }
    else {
      $this->currencies = Currency::loadMultiple($this->configuration['curr_ids']);
    }
  }

}
