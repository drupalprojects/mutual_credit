<?php

use Drupal\mcapi_limits\McapiLimitsException;
use Drupal\mcapi\CurrencyInterface;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\mcapi\Entity\Wallet;

/**
 * preprocessor function for theme callback mcapi_limits_absolute
 */
function template_preprocess_mcapi_limits_absolute(&$vars) {
  $currency = $vars['currency'];
  $summary = $vars['wallet']->getStats($currency->id());
  $limits = mcapi_limits($vars['wallet'])->limits($currency->id());
  $vars['balanceformatted'] = $currency->format($summary['balance']);
  $vars['minformatted'] = $currency->format($limits['min']);
  $vars['maxformatted'] = $currency->format($limits['max']);
  $vars['balance'] = $currency->faux_format($summary['balance']);
  $vars['min'] = $currency->faux_format($limits['min']);
  $vars['max'] = $currency->faux_format($limits['max']);
}

/**
 * preprocessor function for theme callback mcapi_limits_relative
 */
function template_preprocess_mcapi_limits_relative(&$vars) {
  $vars += limits_get_adjusted($vars['currency'], $vars['wallet']);//gives the native values or NULL
  if (!is_null($vars['spend_limit'])) {
    $vars['spendlimitformatted'] = $vars['currency']->format($vars['spend_limit']);
  }
  if (!is_null($vars['earn_limit'])) {
    $vars['earnlimitformatted'] = $vars['currency']->format($vars['earn_limit']);
  }
}

/**
 * preprocessor function for theme callback mcapi_limits_balanced
 * By design this can only be used if the max and min are equidistant from zero
 * Because of limitations of the google gauge,
 * this can only be used if the formatted value is numeric
 */
function template_preprocess_mcapi_limits_balanced(&$vars) {
  $curr_id = $vars['currency']->id();
  $stats = $vars['wallet']->getStats($curr_id);
  $vars['id'] = $vars['id'] = $curr_id.'-ometer-'.$vars['wallet']->id();
  $vars['functionname'] = str_replace('-', '_', $vars['id']);
  $vars += mcapi_limits($vars['wallet'])->limits($curr_id);
  //the google gauge cannot mask the real number with a formatted value
  //so instead of these native numbers we need to present it with a number which resembles the presentation number
  $vars['balance'] = $vars['currency']->faux_format($stats['balance']);
  $vars['min'] = $vars['currency']->faux_format($vars['min']);
  $vars['max'] = $vars['currency']->faux_format($vars['max']);
  echo 'is the library included in template_preprocess_mcapi_limits_balanced?';
}

/**
 * shows the amount available to spend or earn, by factoring in the user's balance
 * @param CurrencyInterface $mcapicurrency
 * @param EntityInterface $wallet
 * @return array
 *   contain native values or NULLs keyed by 'spend_limit' and 'earn_limit'
 */
function limits_get_adjusted(CurrencyInterface $currency, ContentEntityInterface $wallet) {
  $summary = $wallet->getStats($currency->id());
  $minmax = mcapi_limits($wallet)->limits($currency->id());
  $adjusted = array('spend_limit' => NULL, 'earn_limit' => NULL);
  if (isset($minmax['min'])) {
    $adjusted['spend_limit'] = $minmax['min'] - $summary['balance'];
  }
  if (isset($minmax['max'])) {
    $adjusted['earn_limit'] = $minmax['max'] - $summary['balance'];
  }
  return $adjusted;
}

/**
 * implement hook_form_MCAPI_CURRENCY_EDIT_FORM_alter
 * add the limits settings to the currency form
 */
function _mcapi_limits_form_mcapi_currency_edit_form_alter(&$form, $form_state) {
  foreach(\Drupal::service('plugin.manager.mcapi_limits')->getDefinitions() as $name => $plugin) {
    $options[$name] = $plugin['label'];
  }
  $currency = $form_state->getFormObject()->getEntity();
  if ($plugin_id = $form_state->getValue('plugin')) {
    $plugin = mcapi_limits_saved_plugin($currency, $plugin_id);
  }
  else {
    $plugin = mcapi_limits_saved_plugin($currency);
  }
  $form_state->set('limits_plugin', $plugin);
  
  //limits could be managed by drupal OR the entity controller. Drupal offers more flexibility
  $warning[] = t("With great power comes great responsibility!");
  $warning[] = t("The first rule of accounting is: no account should issue more credit than it can redeem.");
  $warning[] = t("Even fiat currencies can be run sustainably if they are used to pay tax.");
  $warning[] = t("Trust in the system can be increased by limiting the right to issue credit to trusted, known, productive actors in the economy, which may or not include the goverment!");
  $warning[] = t("Transactions will be rejected which take accounts beyond these limits.");
  $warning[] = t("Choose which method to use, and configure it.");
  $form['limits'] = array(
    '#title' => t('Balance limits'),
    '#description' => implode(' ', $warning),
    '#type' => 'details',
    '#weight' => 20,
    '#collapsible' => FALSE,
    '#open' => TRUE,
    'plugin' => array(
      '#title' => t('Plugin'),
      '#description' => t('How will the limits be determined?'),
      '#type' => 'select',
      '#options' => $options,
      //'none' is the default plugin name
      '#default_value' => $plugin->id,
      '#weight' => -1,
      '#ajax' => array(
        'callback' => 'limits_settings_subform',
        'wrapper' => 'limits-settings',
        'effect' => 'fade'
      ),
    ),
  );

  //show the options form for the selected plugin
  $form['limits']['limits_settings'] = $plugin->buildConfigurationForm($form, $form_state, $currency);

  //this is the ajax wrapper
  $form['limits']['limits_settings']['#prefix'] = '<div id="limits-settings">';
  $form['limits']['limits_settings']['#suffix'] = '</div>';
  $form['limits']['limits_settings']['#tree'] = TRUE;

  $form['actions']['submit']['#validate'][] = 'mcapi_limits_validate';
  $form['actions']['submit']['#submit'][] = 'mcapi_limits_submit';
}

/**
 * ajax callback
 */
function mcapi_limits_validate(&$form, $form_state) {
  $form_state->get('limits_plugin')->validateConfigurationForm($form, $form_state);
}

/**
 * form submission callback on the submit button
 * save the plugin settings as config.
 */
function mcapi_limits_submit(&$form, $form_state) {
  $plugin = $form_state->get('limits_plugin');
  $plugin->submitConfigurationForm($form, $form_state);

  //load the plugin settings AS CONFIG
  $currency = $form_state->getFormObject()->getEntity();
  foreach ($plugin->getConfiguration() as $key => $val) {
    $currency->setThirdPartySetting('mcapi_limits', $key, $val);
  }
  //this has to go after the plugin has set the values
  $currency->setThirdPartySetting('mcapi_limits', 'plugin', $plugin->id);
  $currency->save();
}

/**
 * Calculate the balance changes that this transaction proposes
 * by convention, if the transaction state < 0 it is NOT COUNTED
 * this is only used in tokens, so far, and in mcapi_limits module
 * incoming transaction can be a transaction object with children or an array
 */
function _transactions_diff(array $cluster) {
  $diff = [];
  foreach ($cluster as $transaction) {
    foreach ($transaction->worth->getValue() as $worth) {
      extract($worth); //makes variables $value and $curr_id
      //we can't prepare the array in advance with zeros so += and -= throws notices
      //instead we just build up an array and add them up later
      $diff[$transaction->payer->target_id][$curr_id][] = -$value;
      $diff[$transaction->payee->target_id][$curr_id][] = $value;
    }
  }
  return $diff;
}

/**
 * ajax callback
 */
function limits_settings_subform($form, $form_state) {
  return $form['limits']['limits_settings'];
}

