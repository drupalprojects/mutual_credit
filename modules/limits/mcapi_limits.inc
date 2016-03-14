<?php

use Drupal\mcapi\Entity\WalletInterface;
use Drupal\mcapi\Entity\Currency;
use Drupal\Core\Form\FormStateInterface;

/**
 * preprocessor function for theme callback mcapi_limits_absolute
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
  $vars['min'] = $currency->format($limits->min($curr_id), Currency::DISPLAY_PLAIN);
  $vars['max'] = $currency->format($limits->max($curr_id), Currency::DISPLAY_PLAIN);
  $vars['balance'] = $currency->format($summary['balance'], Currency::DISPLAY_PLAIN);
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
    $vars['spendlimitformatted'] = ['#markup' => $vars['currency']->format($vars['spend_limit'])];
  }
  if (!is_null($vars['earn_limit'])) {
    $vars['earnlimitformatted'] = ['#markup' => $vars['currency']->format($vars['earn_limit'])];
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
  $vars['balance'] = $currency->format($stats['balance'], Currency::DISPLAY_PLAIN);
  $vars['min'] = $currency->format($limiter->min($curr_id), Currency::DISPLAY_PLAIN);
  $vars['max'] = $currency->format($limiter->max($curr_id), Currency::DISPLAY_PLAIN);
  $vars['minformatted'] = ['#markup' => $currency->format($limiter->min($curr_id))];
  $vars['maxformatted'] = ['#markup' => $currency->format($limiter->max($curr_id))];
}


/**
 * implement hook_form_MCAPI_CURRENCY_EDIT_FORM_alter
 * add the limits settings to the currency form
 */
function _mcapi_limits_form_mcapi_currency_edit_form_alter(&$form, FormStateInterface $form_state) {
  $limitsManager = \Drupal::service('plugin.manager.mcapi_limits');

  foreach($limitsManager->getDefinitions() as $name => $plugin) {
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
      //this is because we need the currency id to build the worth fields in the limits settings form
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
          ':input[name="issuance"]' => ['value' => Currency::TYPE_ACKNOWLEDGEMENT]
        ]
      ],
    ],
    'warning_exchange' => [
      '#type' => 'item',
      '#markup' => t('The integrity of the system depends on account balances averaging zero. Every account, including admin should be committed to returning to zero before closing.'),
      '#weight' => -1,
      '#states' => [
        'visible' => [
          ':input[name="issuance"]' => ['value' => Currency::TYPE_EXCHANGE]
        ]
      ],
    ],
    'warning_commodity' => [
      '#type' => 'item',
      '#markup' => t('All accounts should be positive, except one (issuing) account which is negative according to the value of the commodity stored'),
      '#weight' => -1,
      '#states' => [
        'visible' => [
          ':input[name="issuance"]' => ['value' => Currency::TYPE_COMMODITY]
        ]
      ],
    ],
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
 *
 * @param FormStateInterface $form_state
 * @param CurrencyInterface $currency
 * @return \Drupal\mcapi_limits\Plugin\McapiLimitsInterface
 */
function mcapi_limits_form_state_load_plugin(FormStateInterface $form_state, $currency) {
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
 * ajax callback
 */
function mcapi_limits_validate(&$form, FormStateInterface $form_state) {
  mcapi_limits_form_state_load_plugin($form_state, $form_state->getFormObject()->getEntity())
    ->validateConfigurationForm($form, $form_state);
}

/**
 * form submission callback on the submit button
 * save the plugin settings as config.
 */
function mcapi_limits_submit(&$form, FormStateInterface $form_state) {

  $currency = $form_state->getFormObject()->getEntity();
  $plugin = mcapi_limits_form_state_load_plugin($form_state, $currency);
  $plugin->submitConfigurationForm($form, $form_state);

  //rendered wallets may be affected by changes in balance limits
  Drupal\Core\Cache\Cache::invalidateTags(['mcapi_wallet_view']);

  //load the plugin settings AS CONFIG
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
function limits_settings_subform($form, FormStateInterface $form_state) {
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
  $currencies = $wallet->currenciesAvailable();
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
  return $renderable;
}

