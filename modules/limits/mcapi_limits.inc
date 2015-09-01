<?php

use Drupal\mcapi\CurrencyInterface;
use Drupal\mcapi\WalletInterface;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\mcapi\Entity\Wallet;
use Drupal\mcapi\Exchange;

/**
 * preprocessor function for theme callback mcapi_limits_absolute
 */
function template_preprocess_mcapi_limits_absolute(&$vars) {
  $currency = $vars['currency'];
  $curr_id = $currency->id();
  $summary = $vars['wallet']->getStats($currency->id());

  $limits = \Drupal::service('mcapi_limits.wallet_limiter')
    ->setwallet($vars['wallet']);
  $vars['balanceformatted'] = $currency->format($summary['balance'], Currency::FORMAT_NORMAL);
  $vars['minformatted'] = $currency->format($limits->min($curr_id), Currency::FORMAT_NORMAL);
  $vars['maxformatted'] = $currency->format($limits->max($curr_id), Currency::FORMAT_NORMAL);
  $vars['min'] = $currency->format($limits->min($curr_id), Currency::FORMAT_PLAIN);
  $vars['max'] = $currency->format($limits->max($curr_id), Currency::FORMAT_PLAIN);
  $vars['balance'] = $currency->format($summary['balance'], Currency::FORMAT_PLAIN);
}

/**
 * preprocessor function for theme callback mcapi_limits_relative
 */
function template_preprocess_mcapi_limits_relative(&$vars) {

  $balance = $vars['wallet']->getStat($currency->id(), 'balance');
  $limiter = \Drupal::service('mcapi_limits.wallet_limiter')
    ->setwallet($vars['wallet']);

  $vars['earn_limit'] = $limiter->earn_limit($vars['currency']->id(), $balance);
  $vars['spend_limit'] = $limiter->spend_limit($vars['currency']->id(), $balance);

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
 * Also because of limitations of the google charts gauge visualisation,
 * this can only be used if the formatted value is numeric
 */
function template_preprocess_mcapi_limits_balanced(&$vars) {
  $currency = $vars['currency'];
  $curr_id = $currency->id();
  $wallet = $vars['wallet'];
  $stats = $wallet->getStats($curr_id);
  $vars['id'] = $vars['id'] = $curr_id.'-ometer-'.$wallet->id();
  $vars['functionname'] = str_replace('-', '_', $vars['id']);
  //the google gauge cannot mask the real number with a formatted value
  
  $limiter = \Drupal::service('mcapi_limits.wallet_limiter')
    ->setwallet($wallet);
  $vars['balance'] = $currency->format($summary['balance'], Currency::FORMAT_PLAIN);
  $vars['min'] = $currency->format($limits->min($curr_id), Currency::FORMAT_PLAIN);
  $vars['max'] = $currency->format($limits->max($curr_id), Currency::FORMAT_PLAIN);
}


/**
 * implement hook_form_MCAPI_CURRENCY_EDIT_FORM_alter
 * add the limits settings to the currency form
 */
function _mcapi_limits_form_mcapi_currency_edit_form_alter(&$form, $form_state) {
  $limitsManager = \Drupal::service('plugin.manager.mcapi_limits');

  foreach($limitsManager->getDefinitions() as $name => $plugin) {
    $options[$name] = $plugin['label'];
  }
  $currency = $form_state->getFormObject()->getEntity();
  if ($plugin_id = $form_state->getValue('plugin')) {
    //the form was submitted by ajax and we retain the previous value
    $plugin = $limitsManager->createInstance($plugin_id, ['currency' => $currency]);
  }
  else {
    //the first time the form has loaded
    $plugin = $limitsManager->createInstanceCurrency($currency);
  }
  $form_state->set('limits_plugin', $plugin);

  //limits could be managed by drupal OR the entity controller. Drupal offers more flexibility
  $warning[] = t("Transactions will be rejected which take accounts beyond these limits.");
  $warning[] = t("In an exchange currency, no accounts, even admin accounts, should issue more credit than it can redeem.");
  $warning[] = t("Trust in the system can be increased by limiting the right to issue credit to trusted, known, productive actors in the economy, which may or not include the goverment!");
  $warning[] = t("In a fiat (acknowledgement) currency usually only the admin account will go negative i.e. lissue the credit.");
  $warning[] = t("A commodity currency is the same as a fiat, except all the admin debt corresponds to a stored commodity.");
  $form['limits'] = [
    '#title' => t('Balance limits'),
    '#description' => implode(' ', $warning),
    '#type' => 'details',
    '#weight' => 20,
    '#collapsible' => FALSE,
    '#open' => TRUE,
    'plugin' => [
      '#title' => t('Plugin'),
      '#description' => t('How will the limits be determined?'),
      '#type' => 'select',
      '#options' => $options,
      //'none' is the default plugin name
      '#default_value' => $plugin->id,
      '#weight' => -1,
      '#ajax' => [
        'callback' => 'limits_settings_subform',
        'wrapper' => 'limits-settings',
        'effect' => 'fade'
      ],
    ],
  ];

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
  $form_state->get('limits_plugin')
    ->validateConfigurationForm($form, $form_state);
}

/**
 * form submission callback on the submit button
 * save the plugin settings as config.
 */
function mcapi_limits_submit(&$form, $form_state) {
  $plugin = $form_state->get('limits_plugin');
  $plugin->submitConfigurationForm($form, $form_state);

  //rendered wallets may be affected by changes in balance limits
  Drupal\Core\Cache\Cache::invalidateTags(['mcapi_wallet_view']);

  //load the plugin settings AS CONFIG
  $currency = $form_state->getFormObject()->getEntity();
  foreach ($plugin->getConfiguration() as $key => $val) {
    $currency->setThirdPartySetting('mcapi_limits', $key, $val);
  }
  //this has to go after the plugin has set the values
  $currency->setThirdPartySetting('mcapi_limits', 'plugin', $plugin->id);
  $currency->save();

  //@todo would be nice to compare the old balance limits with the new and log only if there are changes
  \Drupal::logger('mcapi')->notice(
    'User @uid saved currency @name',
    ['@uid' => \Drupal::currentUser()->id(), '@name' => $currency->label()]
  );
}


/**
 * ajax callback
 */
function limits_settings_subform($form, $form_state) {
  return $form['limits']['limits_settings'];
}

/**
 * callback to build a render array
 * used in hook_wallet_view, views, & blocks
 *
 * @param WalletInterface $wallet
 * @param array $curr_ids
 *   limit the display to the currencies given.
 *
 * @return array
 *   a renderable array
 */
function mcapi_view_limits(WalletInterface $wallet, $curr_ids = []) {
  $renderable = [];
  $currencies = Exchange::currenciesAvailable($wallet);
  if ($curr_ids) {
    $currencies = array_intersect_key($currencies, array_flip($curr_ids));
  }
  foreach ($currencies as $currency) {
    if ($currency->getThirdPartySetting('mcapi_limits', 'plugin', 'none') == 'none') {
      continue;
    }
    if ($currency->getThirdPartySetting('mcapi_limits', 'display_relative', 'absolute') == TRUE) {
      $theme = 'mcapi_limits_relative';
    }
    else {
      $limiter = \Drupal::service('mcapi_limits.wallet_limiter')
        ->setwallet($wallet);
      $max = $limiter->max($currency->id());
      if ($max && (-$max == $limiter->min($currency->id()))) {
        $theme = 'mcapi_limits_balanced';
      }
      else {
        $theme = 'mcapi_limits_absolute';
      }
    }
    $renderable[$currency->id()]['mcapi_limits'] = [
      '#theme' => $theme,
      '#wallet' => $wallet,
      '#currency' => $currency,
    ];
  }
  //@todo how do we attach css in twig or in preprocessing?
  $renderable['#attached']['library']['mcapi/mcapi.wallets'];
  //@todo how do we attach css in twig or in preprocessing?
  $renderable['#attached']['library']['mcapi/mcapi.gchart'];
  return $renderable;
}

