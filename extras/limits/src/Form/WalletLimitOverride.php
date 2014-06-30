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
    //to do that we have to know all the currencies in the all the active exchanges the wallets parent is in.
    $wallet = $this->wallet;
    $owner = $wallet->getOwner();
    $exchanges = referenced_exchanges($owner);
    if (empty($exchanges)) {
      drupal_set_message(t("!name is not currently in any active exchange"), array('!name' => $owner->getlabel()));
      return $form;
    }

    $overridden = mcapi_limits($wallet)->saved_overrides();
    //TODO the limits are no longer stored in the currency

    foreach ($wallet->currencies_available() as $curr_id => $currency) {
      $config = mcapi_limits_saved_plugin($currency)->getConfiguration();
      if (!$config || $config['plugin'] == 'none') continue;
      $defaults = mcapi_limits($wallet)->default_limits($currency);
      $limits = array_filter($defaults);
      if (array_key_exists('min', $limits)) {
        $desc[] = t('Min: !worth', array('!worth' => $currency->format($limits['min'])));
      }
      if (array_key_exists('max', $limits)) {
        $desc[] = t('Max: !worth', array('!worth' => $currency->format($limits['max'])));
      }
      $form[$curr_id] = array(
        '#type' => 'fieldset',
        '#tree' => TRUE,
        '#title' => $currency->label(),
        '#description' => $desc ?
          t('Default values !values', array('!values' => implode(', ', $desc))) :
          t('No default limits are set')
      );
      if ($config['override']) {
        //for now the per-wallet override allows admin to declare absolute min and max per user.
        //the next thing would be for the override to support different plugins and settings per user.
        //this should be in the plugin
        $form[$curr_id]['override'] = array(
          '#title' => t('Values for this wallet'),
          '#curr_id' => $curr_id,
        	'#type' => 'minmax',
          '#default_value' => array(
            'min' => @$overridden[$curr_id]['min'],
            'max' => @$overridden[$curr_id]['max']
          ),
          '#placeholder' => array(
          	'min' => $defaults['min'],
            'max' => $defaults['max']
          )
        );
      }
      else {//currency is not overridable
        //don't show anything
        $form[$curr_id]['override'] = array(
        	'#markup' => t('This currency is not overridable')
        );
      }
    }
    echo 'placeholder'; print_r($defaults);
    //TODO the currencies could be sorted by weight; v low priority!

    if (element_children($form)) {
      $form['help'] = array(
        '#markup' => t("Leave fields blank to use the currencies' own settings"),
        '#weight' => -1
      );
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
    $wid = $this->wallet->id();
    //clear db and rewrite
    try {
      $t = db_transaction();
      db_delete('mcapi_wallets_limits')->condition('wid', $wid)->execute();
      $q = db_insert('mcapi_wallets_limits')->fields(array('wid', 'curr_id', 'min', 'max', 'editor', 'date'));
      $insert = FALSE;
      foreach ($form_state['values'] as $curr_id => $values) {
        if (empty($values['override']['min']) && empty($values['override']['max'])) continue;
        $q->values(array(
          'wid' => $wid,
          'curr_id' => $curr_id,
          'min' => $values['override']['min'][0]['value'],
          'max' => $values['override']['max'][0]['value'],
          'editor' => \Drupal::CurrentUser()->id(),
          'date' => REQUEST_TIME
        ));
        $insert = TRUE;
      }
      if ($values) $q->execute();
      else drupal_set_message('No limits were overridden');
    }
    catch (\Exception $e) {
      $t->rollback();
      //are there security concerns about showing the user this message?
      drupal_set_message('Failed to save limit overrides: '.$e->getMessage());
    }
    //TODO clear the wallet cache???
    $form_state['redirect_route'] = array(
    	'route_name' => 'mcapi.wallet_view',
      'route_parameters' => array('mcapi_wallet' => $this->wallet->id())
    );

  }



}