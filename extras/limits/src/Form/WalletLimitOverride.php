<?php

/**
 * @file
 * Contains Drupal\mcapi_limits\Form\WalletLimitOverride
 *
 * Allow an administrator to set the per-wallet limits
 *
 * @todo would be more elegant if the overrides min and max were
 * wallet properties themselves then this build form would be more automatic
 */

namespace Drupal\mcapi_limits\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Field\FieldDefinition;

class WalletLimitOverride extends FormBase {

  function __construct() {
    $request = \Drupal::request();
    //TODO this is surely not the official way to to retieve the entity from the route
    $this->wallet = mcapi_request_get_entity($request);
  }

  /**
   * {@inheritdoc}
   */
  public function getFormID() {
    return 'wallet_limits_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, array &$form_state) {
    //this is tricky. We need to know all the currency that could go in the wallet.
    //to do that we have to know all the currencies in the all the exchanges the wallets parent is in.
    $owner = $this->wallet->getOwner();
    $exchanges = referenced_exchanges($owner);
    if (empty($exchanges)) {
      drupal_set_message(t("!name is not currently in any active exchange"), array('!name' => $owner->getlabel()));
      return $form;
    }

    foreach (exchange_currencies($exchanges) as $curr_id => $currency) {
      if (property_exists($currency, 'limits_settings') && is_array($currency->limits_settings) && isset($currency->limits_settings['override'])) {
        $currency_defaults = $currency->limits_settings;
      }
      else continue;
      //for now the per-wallet override allows admin to declare absolute min and max per user.
      //the next thing would be for the override to support different plugins and settings per user.
      $form[$curr_id] = array(
        '#type' => 'fieldset',
        '#title' => $currency->label(),
        '#tree' => TRUE,
        '#description' => t('Default values min: !min, max: !max', array(
          '!min' => $currency->format($this->wallet->limits[$curr_id]['max']),
          '!max' => $currency->format($this->wallet->limits[$curr_id]['min']),
        )),
      );
      //this should be in the plugin
      $form[$curr_id]['override'] = array(
        '#title' => t('Values for this wallet'),
        '#curr_id' => $curr_id,
      	'#type' => 'minmax',
        '#default_value' => array(
          'min' => $this->wallet->limits_override[$curr_id]['min'],
          'max' => $this->wallet->limits_override[$curr_id]['max']
        )
      );
    }

    //TODO the currencies could be sorted by weight; v low priority!

    if (element_children($form)) {
      $form['submit'] = array(
      	'#type' => 'submit',
        '#value' => t('Save'),
        '#weight' => 10
      );
    }
    else {
      $form['empty']['#markup'] = t('This wallet cannot use any currencies which can be overridden.');
    }
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, array &$form_state) {

  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, array &$form_state) {
    form_state_values_clean($form_state);
    foreach ($form_state['values'] as $curr_id => $minmax) {
      $this->wallet->limits_override[$curr_id] = array(
        'min' => $minmax['override']['min']['value'],
        'max' => $minmax['override']['max']['value']
      );
    }
    $this->wallet->save();
    $form_state['redirect_route'] = array(
    	'route_name' => 'mcapi.wallet_view',
      'route_parameters' => array('mcapi_wallet' => $this->wallet->id())
    );

  }



}