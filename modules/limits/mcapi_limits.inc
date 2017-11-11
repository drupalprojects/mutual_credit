<?php

/**
 * @file
 * Helper functions.
 */

use Drupal\mcapi\Entity\WalletInterface;
use Drupal\mcapi\Entity\CurrencyInterface;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Form\FormStateInterface;

/**
 * Preprocessor function for theme callback mcapi_limits_absolute.
 */
function template_preprocess_mcapi_limits_absolute(&$vars) {
  $currency = $vars['currency'];
  $curr_id = $currency->id();
  $summary = $vars['wallet']->getStats($currency->id());

  $limits = \Drupal::service('mcapi_limits.wallet_limiter')
    ->setwallet($vars['wallet']);
  $vars['balanceformatted'] = ['#markup' => $currency->format($summary['balance'])];
  $vars['minformatted'] = ['#markup' => $currency->format($limits->min($curr_id))];
  $vars['maxformatted'] = ['#markup' => $currency->format($limits->max($curr_id))];
  $vars['min'] = $currency->format($limits->min($curr_id), CurrencyInterface::DISPLAY_PLAIN);
  $vars['max'] = $currency->format($limits->max($curr_id), CurrencyInterface::DISPLAY_PLAIN);
  $vars['balance'] = $currency->format($summary['balance'], CurrencyInterface::DISPLAY_PLAIN);
}

/**
 * Preprocessor function for theme callback mcapi_limits_relative.
 */
function template_preprocess_mcapi_limits_relative(&$vars) {
  $balance = $vars['wallet']->getStat($vars['currency']->id(), 'balance');
  $limiter = \Drupal::service('mcapi_limits.wallet_limiter')
    ->setwallet($vars['wallet']);

  $vars['earn_limit'] = $limiter->earnLimit($vars['currency']->id(), $balance);
  $vars['spend_limit'] = $limiter->spendLimit($vars['currency']->id(), $balance);

  if (!is_null($vars['spend_limit'])) {
    $vars['spendlimitformatted'] = ['#markup' => $vars['currency']->format($vars['spend_limit'])];
  }
  if (!is_null($vars['earn_limit'])) {
    $vars['earnlimitformatted'] = ['#markup' => $vars['currency']->format($vars['earn_limit'])];
  }
}

/**
 * Preprocessor function for theme callback mcapi_limits_balanced.
 *
 * By design this can only be used if the max and min are equidistant from zero
 * Also because of limitations of the google charts gauge visualisation,
 * this can only be used if the formatted value is numeric.
 */
function template_preprocess_mcapi_limits_balanced(&$vars) {
  $currency = $vars['currency'];
  $curr_id = $currency->id();
  $wallet = $vars['wallet'];
  $stats = $wallet->getStats($curr_id);
  $vars['id'] = $vars['id'] = $curr_id . '-ometer-' . $wallet->id();
  $vars['functionname'] = str_replace('-', '_', $vars['id']);
  // The google gauge cannot mask the real number with a formatted value.
  $limiter = \Drupal::service('mcapi_limits.wallet_limiter')
    ->setwallet($wallet);
  $vars['balance'] = $currency->format($stats['balance'], CurrencyInterface::DISPLAY_PLAIN);
  $vars['min'] = $currency->format($limiter->min($curr_id), CurrencyInterface::DISPLAY_PLAIN);
  $vars['max'] = $currency->format($limiter->max($curr_id), CurrencyInterface::DISPLAY_PLAIN);
  $vars['minformatted'] = ['#markup' => $currency->format($limiter->min($curr_id))];
  $vars['maxformatted'] = ['#markup' => $currency->format($limiter->max($curr_id))];
}

/**
 * Implements hook_form_MCAPI_CURRENCY_EDIT_FORM_alter().
 *
 * Add the limits settings to the currency form.
 */
function _mcapi_limits_form_mcapi_currency_edit_form_alter(&$form, FormStateInterface $form_state) {
  $limitsManager = \Drupal::service('plugin.manager.mcapi_limits');

  foreach ($limitsManager->getDefinitions() as $name => $plugin) {
    $options[$name] = $plugin['label'];
  }
  $currency = $form_state->getFormObject()->getEntity();
  $form['limits'] = [
    '#title' => t('Balance limits'),
    '#description' => t("Transactions will be rejected which take accounts beyond these limits."),
    '#type' => 'details',
    '#weight' => 20,
    '#collapsible' => FALSE,
    '#open' => TRUE,
  ];
  if ($currency->isNew()) {
    $form['limits']['note'] = [
      // We need currencyID to build worth fields in the limits settings form.
      '#markup' => t('Limits can only be configured after the currency is saved.'),
      '#weight' => 20,
    ];
    return;
  }
  $plugin = mcapi_limits_form_state_load_plugin($form_state, $currency);
  $form_state->set('limits_plugin', $plugin->id);

  $form['limits'] += [
    'warning_acknowledgement' => [
      '#type' => 'item',
      '#markup' => t('Usually just one admin account can go below zero, and the more it does so the less the units are circulated between members, but just accumulate in their accounts, never to be spent.'),
      '#weight' => -1,
      '#states' => [
        'visible' => [
          ':input[name="issuance"]' => ['value' => CurrencyInterface::TYPE_ACKNOWLEDGEMENT],
        ],
      ],
    ],
    'warning_exchange' => [
      '#type' => 'item',
      '#markup' => t('The integrity of the system depends on account balances averaging zero. Every account, including admin should be committed to returning to zero before closing.'),
      '#weight' => -1,
      '#states' => [
        'visible' => [
          ':input[name="issuance"]' => ['value' => CurrencyInterface::TYPE_PROMISE],
        ],
      ],
    ],
    'warning_asset' => [
      '#type' => 'item',
      '#markup' => t('All accounts should be positive, except one (issuing) account which is negative according to the value of the asset stored'),
      '#weight' => -1,
      '#states' => [
        'visible' => [
          ':input[name="issuance"]' => ['value' => CurrencyInterface::TYPE_ASSET],
        ],
      ],
    ],
    'plugin' => [
      '#title' => t('Plugin'),
      '#description' => t('How will the limits be determined?'),
      '#type' => 'select',
      '#options' => $options,
      // 'none' is the default plugin name.
      '#default_value' => $plugin->id,
      '#weight' => -1,
      '#ajax' => [
        'callback' => 'mcapi_limits_plugin_settings_subform',
        'wrapper' => 'limits-plugin-settings',
        'effect' => 'fade',
      ],
    ],
  ];

  // Show the options form for the selected plugin.
  $form['limits']['plugin_settings'] = $plugin->buildConfigurationForm($form, $form_state, $currency);
  // This is the ajax wrapper.
  $form['limits']['plugin_settings']['#prefix'] = '<div id="limits-plugin-settings">';
  $form['limits']['plugin_settings']['#suffix'] = '</div>';
  $form['limits']['plugin_settings']['#tree'] = TRUE;
  $form['actions']['submit']['#validate'][] = 'mcapi_limits_validate';
  $form['actions']['submit']['#submit'][] = 'mcapi_limits_submit';
}

/**
 * Get the limits plugin.
 *
 * @param FormStateInterface $form_state
 *   The form_state.
 * @param CurrencyInterface $currency
 *   The currency.
 *
 * @return \Drupal\mcapi_limits\Plugin\McapiLimitsInterface
 *   The Limits object.
 */
function mcapi_limits_form_state_load_plugin(FormStateInterface $form_state, CurrencyInterface $currency) {
  $limitsManager = \Drupal::service('plugin.manager.mcapi_limits');
  if ($plugin_id = $form_state->getValue('plugin')) {
    $plugin = $limitsManager->createInstance(
      $plugin_id,
      ['currency' => $currency]
    );
  }
  else {
    $plugin = $limitsManager->createInstanceCurrency($currency);
  }
  return $plugin;
}

/**
 * Ajax callback.
 */
function mcapi_limits_validate(&$form, FormStateInterface $form_state) {
  mcapi_limits_form_state_load_plugin($form_state, $form_state->getFormObject()->getEntity())
    ->validateConfigurationForm($form, $form_state);
}

/**
 * Form submission callback on the submit button.
 *
 * Save the plugin settings as config.
 */
function mcapi_limits_submit(&$form, FormStateInterface $form_state) {

  $currency = $form_state->getFormObject()->getEntity();
  $plugin = mcapi_limits_form_state_load_plugin($form_state, $currency);
  $plugin->submitConfigurationForm($form, $form_state);

  // Rendered wallets may be affected by changes in balance limits.
  Cache::invalidateTags(['mcapi_wallet_view']);

  // Load the plugin settings AS CONFIG.
  foreach ($plugin->getConfiguration() as $key => $val) {
    $currency->setThirdPartySetting('mcapi_limits', $key, $val);
  }
  // This has to go after the plugin has set the values.
  $currency->setThirdPartySetting('mcapi_limits', 'plugin', $plugin->id);
  $currency->save();

  // @todo would be nice to compare the old balance limits with the new and log only if there are changes
  \Drupal::service('logger.channel.mcapi')->notice(
    'User @uid saved currency @name',
    ['@uid' => \Drupal::currentUser()->id(), '@name' => $currency->label()]
  );
}

/**
 * Ajax callback.
 */
function mcapi_limits_plugin_settings_subform($form, FormStateInterface $form_state) {
  return $form['limits']['plugin_settings'];
}

/**
 * Show a wallets limits.
 *
 * Used in hook_wallet_view, views, & blocks.
 *
 * @param WalletInterface $wallet
 *   The wallet.
 * @param array $curr_ids
 *   Limit the display to the currencies given.
 * @param bool $relative
 *   Show the limits relative to the user's current balance.
 *
 * @return array
 *   A renderable array.
 *
 * @todo add the $relative parameter to calling functions, and related config.
 */
function mcapi_view_limits(WalletInterface $wallet, $curr_ids = [], $relative = FALSE) {
  $currencies = mcapi_currencies_available($wallet);
  if ($curr_ids) {
    $currencies = array_intersect_key($currencies, array_flip($curr_ids));
  }
  $renderable = [];
  foreach ($currencies as $currency) {
    if ($currency->getThirdPartySetting('mcapi_limits', 'plugin', 'none') == 'none') {
      continue;
    }
    if ($relative) {
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
  return $renderable;
}
