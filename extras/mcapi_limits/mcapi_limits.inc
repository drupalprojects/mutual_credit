<?php


use Drupal\mcapi_limits\McapiLimitsException;

/**
 * transferred implementation of hook_mcapi_transaction_validate().
 * check that none of the wallets involved goes over its limits,
 * and add excecptions to the main transaction object when they do
 * @param array $cluster
 *   transaction and its children side by side
 */
function _mcapi_limits_mcapi_transaction_validate($cluster) {
  //first add up all the transactions
  $errors = $messages = array();
  //to exclude the current transactions from the sum of saved transactions
  //compare the resulting balances for each wallet with its limits

  foreach (_transactions_diff($cluster) as $wid => $percurrency) {
    $wallet = entity_load('mcapi_wallet', $wid);
    foreach ($percurrency as $currcode => $diffs) {
      //check to see if any of the skips apply.
      $currency = entity_load('mcapi_currency', $currcode);
      //upgraded sites need to check for the presence of the skip property
      $skips = isset($currency->limits['skip']) ? $currency->limits['skip'] : array('user1' => 0, 'owner' => 0, 'auto' => 0, 'reservoir' => 0 );
      if ($skips['user1'] && \Drupal::currentUser()->id() == 1) {
        $messages[$currcode] = t(
            "Skipped @currency balance limit check because you are user 1.",
            array('@currency' => $currency->name)
        );
        continue;
      }
      elseif ($skips['owner'] && \Drupal::currentUser()->id() == $currency->uid) {
        $messages[$currcode] = t(
            "Skipped @currency balance limit check because you are the currency owner.",
            array('@currency' => $currency->name)
        );
        continue;
      }
      elseif ($skips['auto'] && current($cluster)->type == 'auto') {
        $messages[$currcode] = t(
            "Skipped balance limit checks for automated transactions with @currency.",
            array('@currency' => $currency->name)
        );
        continue;
      }
      $summary = $wallet->getStats($currency->id());
      $projected = @$summary['balance'] + array_sum($diffs);
      $limits = wallet_get_limit($wallet, $currency);
      //we could send 'worth' values to the exception, but they are hard to work with
      //instead we'll send the currency and the quantities
      if ($projected > 0 && $limits['max'] && $projected > $limits['max']) {
        $errors[$wid][] = array(
          'currency' => $currency,
          'limit' => $limits['max'],
          'projected' => $projected,
          'excess' => $projected - $limits['max'],
          'wallet' => $wallet
        );
      }
      elseif ($projected < 0 && $limits['min'] !== NULL && $projected < $limits['min']) {
        $errors[$wid][] = array(
          'currency' => $currency,
          'limit' => $limits['min'],
          'projected' => $projected,
          'excess' => $limits['min'] - $projected,
          'wallet' => $wallet
        );
      }
    }
  }
  //all balance errors are on the main transaction and can stop.
  $main_transaction = reset($cluster);
  if (count($errors)) {
    foreach ($errors as $wid => $info) {
      foreach ($info as $delta => $vars) {
        //actually the mcapi_transaction_validate hook doesn't require that errors be thrown
        $main_transaction->exceptions[] = new McapiLimitsException($vars['currency'], $vars['limit'], $vars['projected'], $vars['excess'], $vars['wallet']);
      }
    }
  }
  if (count($messages) && \Drupal::currentUser()->hasPermission('configure mcapi')) {
    foreach ($messages as $currcode => $message) {
      drupal_set_message($message, 'warning', FALSE);
    }
  }
}

/*
 * preprocessor function for theme callback mcapi_limits_absolute
 */
function template_preprocess_mcapi_limits_absolute(&$vars) {
  $currency = $vars['currency'];
  $summary = $vars['wallet']->getStats($currency->id());
  $vars['balance'] = $summary['balance'];
  $vars['balanceformatted'] = $currency->format_decimal($vars['balance']);
  $vars += wallet_get_limit($vars['wallet'], $currency);
  $vars['minformatted'] = $currency->format_decimal($vars['min']);
  $vars['maxformatted'] = $currency->format_decimal($vars['max']);
}
/*
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
/*
 * preprocessor function for theme callback mcapi_limits_balanced
 * By design this can only be used if the max and min are equidistant from zero
 * Because of limitations of the google gauge,
 * this can only be used if the formatted value is numeric
 */
function template_preprocess_mcapi_limits_balanced(&$vars) {
  $currency = $vars['currency'];
  $stats = $vars['wallet']->getStats($currency->id());
  $vars['id'] = $vars['id'] = $currency->id().'-ometer-'.$vars['wallet']->id();
  $vars['functionname'] = str_replace('-', '_', $vars['id']);
  $vars += wallet_get_limit($vars['wallet'], $currency);
  //the google gauge cannot mask the real number with a formatted value
  //so instead of these native numbers we need to present it with a number which resembles the presentation number
  $vars['balance'] = $currency->format_decimal($stats['balance']);
  $vars['min'] = $currency->format_decimal($vars['min']);
  $vars['max'] = $currency->format_decimal($vars['max']);

  //$vars['maxformatted'] = $currency->format($vars['max']);
  //$vars['minformatted'] = $currency->format($vars['min']);
  //$vars['balanceformatted'] = $currency->format_decimal($vars['balance']);
  module_load_include('inc', 'mcapi', 'extras/wallet_preprocess');
  mcapi_add_gchart_js('gauge');
}

/*
 * shows the amount available to spend or earn, by factoring in the user's balance
 * @param CurrencyInterface $mcapicurrency
 * @param EntityInterface $wallet
 * @return array
 *   contain native values or NULLs keyed by 'spend_limit' and 'earn_limit'
 */
function limits_get_adjusted(CurrencyInterface $currency, EntityInterface $wallet) {
  $summary = $wallet->getStats($currency->id());
  $minmax = wallet_get_limit($wallet, $currency);
  $adjusted = array('spend_limit' => NULL, 'earn_limit' => NULL);
  if (isset($minmax['min'])) {
    $adjusted['spend_limit'] = $summary['balance'] - $minmax['min'];
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

  $currency = $form_state['controller']->getEntity();

  //I didn't expect to have to do this, for ajax
  if (isset($form_state['values'])) {
    $currency->limits_plugin = $form_state['values']['limits_plugin'];
  }
  $selected_plugin = mcapi_limits_plugin($currency);

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
    '#type' => 'fieldset',
    '#weight' => 20,
    '#collapsible' => FALSE,
    '#collapsed' => FALSE,
    'limits_plugin' => array(
      '#title' => t('Plugin'),
      '#description' => t('How will the limits be determined?'),
      '#type' => 'select',
      '#options' => $options,
      //'none' is the default plugin name
      '#default_value' => $currency->limits_plugin ? $currency->limits_plugin : '',
      '#weight' => -1,
      '#ajax' => array(
        'callback' => 'limits_settings_subform',
        'wrapper' => 'limits-settings',
        'effect' => 'fade'
      ),
    ),
  );

  //depending on which plugin is selected, show the options form
  $form['limits']['limits_settings'] = $selected_plugin->settingsForm();

  $form['limits']['limits_settings']['#prefix'] = '<div id="limits-settings">';
  $form['limits']['limits_settings']['#suffix'] = '</div>';
  $form['limits']['limits_settings']['#tree'] = TRUE;
}

/*
 * Calculate the balance changes that this transaction proposes
* by convention, if the transaction state < 0 it is NOT COUNTED
* this is only used in tokens, so far, and in mcapi_limits module
* incoming transaction can be a transaction object with children or an array
*/
function _transactions_diff(array $cluster) {
  $diff = array();
  foreach ($cluster as $transaction) {
    //this throws an error sometimes
    foreach ($transaction->worths[0] as $currcode => $worth) {
      //we can't prepare the array in advance with zeros so += and -= throws notices
      //instead we just build up an array and add them up when we need them
      $diff[$transaction->payer->value][$currcode][] = -$worth->value;
      $diff[$transaction->payee->value][$currcode][] = $worth->value;
    }
  }
  return $diff;
}