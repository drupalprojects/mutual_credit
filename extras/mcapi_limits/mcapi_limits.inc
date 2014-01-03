<?php

function _mcapi_limits_mcapi_transaction_validate($cluster) {
  //first add up all the transactions
  $errors = $messages = array();
  //to exclude the current transactions from the sum of saved transactions
  //compare the resulting balances for each user with their limits
  foreach (_transactions_diff($cluster) as $uid => $percurrency) {
    $account = user_load($uid);
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
      elseif ($skips['reservoir'] && $uid == $currency->reservoir )  {
        $messages[$currcode] = t(
            "Skipped balance limit checks for @currency transactions involving the reservoir account.",
            array('@currency' => $currency->name)
        );
        continue;
      }
      $summary = mcapi_account_summary($account, $currency);
      $projected = $summary['balance'] + array_sum($diffs);
      $limits = mcapi_limits_plugin($currency)->getLimits($account);
      //we could send 'worth' values to the exception, but that seems like a hassle.
      //instead we'll send the currency and the quantities
      if ($projected > 0 && $limits['max'] && $projected > $limits['max']) {
        $errors[$uid][] = array(
            'currency' => $currency,
            'projected' => $projected,
            'limit' => $limits['max'],
            'excess' => $projected - $limits['max'],
        );
      }
      elseif ($projected < 0 && $limits['min'] != NULL && $projected < $limits['min']) {
        $errors[$uid][] = array(
            'currency' => $currency,
            'projected' => $projected,
            'limit' => $limits['min'],
            'excess' => $limits['min'] - $projected,
        );
      }
    }
  }
  //all balance errors are on the main transaction and can stop.
  $main_transaction = reset($cluster);
  if (count($errors)) {
    foreach ($errors as $uid => $info) {
      foreach ($info as $delta => $vars) {
        //actually the mcapi_transaction_validate hook doesn't require that errors be thrown
        $main_transaction->exceptions[] = new McapiLimitsException($vars['currency'], $uid, $vars['projected'], $vars['limit'], $vars['excess']);
      }
    }
  }
  if (count($messages) && \Drupal::currentUser()->hasPermission('manage all transactions')) {
    foreach ($messages as $currcode => $message) {
      drupal_set_message($message, 'warning', FALSE);
    }
  }
}

function _mcapi_limits_form_user_form_alter(&$form, &$form_state) {
  $account = $form_state['controller']->getEntity();
  foreach (mcapi_get_available_currencies() as $currcode => $currency) {
    if ($currency->limits_plugin == 'none') continue;
    if (property_exists($currency, 'limits_settings') && is_array($currency->limits_settings) && array_key_exists('personal', $currency->limits_settings)) {
      $inherit = $currency->limits_settings['personal'];
    }
    else continue;
    if (\Drupal::currentUser()->hasPermission('configure all currencies') || \Drupal::currentUser()->id() == $currency->uid->value) {
      $access = TRUE;
    }
    //fire up the plugin
    $plugin = mcapi_limits_plugin($currency);
    $base_limits = $plugin->getBaseLimits($account);
    //for now the personal override allows admin to declare absolute min and max per user.
    //the next thing would be for the override to support different plugins and settings per user.
    $form['limits_personal'][$currcode] = array(
        '#type' => 'fieldset',
        '#title' => $currency->label(),
        '#description' => t('Basic min: !min, max: !max', array(
            '!min' => $currency->format($base_limits['min']),
            '!max' => $currency->format($base_limits['max'])
        )),
        '#attributes' =>  new Attribute(array('style' => 'float:left;')),
        '#disabled' => !(\Drupal::currentUser()->hasPermission('configure all currencies') || \Drupal::currentUser()->id() == $currency->uid->value)
    );
    $defaults = $plugin->getLimits($account);
    if ($plugin->id == 'balanced') {
      //would be a real hasle to get this inside the plugin and do this.
      $form['limits_personal'][$currcode]['minmax'] = $plugin->widget($defaults);
    }
    else {
      $form['limits_personal'][$currcode]['minmax'] = array(
          '#type' => 'minmax',
          '#default_value' => $defaults,
          '#currcode' => $currency->id()
      );
    }
  }
  //add a containing fieldset if needed
  if (isset($form['limits_personal'])) {
    $form['limits_personal'] += array(
        '#title' => t('Personal balance limits, set manually'),
        '#description' => t('These override any limits defined in the currency.') .' '.
        t("Leave blank to inherit the values from the currency's settings"),
        '#type' => 'fieldset',
        '#weight' => 2
    );
  }
}

/*
 * preprocessor function for theme callback mcapi_limits_absolute
 */
function template_preprocess_mcapi_limits(&$vars) {
  $currency = $vars['currency'];
  $summary = mcapi_account_summary($vars['account'], $currency);
  $vars['data'] = mcapi_limits_plugin($currency)->getLimits($vars['account']);
  $vars['data']['balance'] = $summary['balance'];
  $vars['balance'] = $currency->format($vars['data']['balance']);
  $vars['max'] = $currency->format($vars['data']['max']);
  $vars['min'] = $currency->format($vars['data']['min']);
  $vars['currencyname'] = $currency->label();
}
/*
 * preprocessor function for theme callback mcapi_limits_relative
 */
function template_preprocess_mcapi_limits_relative(&$vars) {
  $currency = $vars['currency'];
  $adjustments = limits_get_adjusted($currency, $vars['account']);
  $vars['spend_limit'] = $currency->format($adjustments['spend_limit']);
  $vars['earn_limit'] = $currency->format($adjustments['earn_limit']);
  $vars['currencyname'] = $currency->label();
}
/*
 * preprocessor function for theme callback mcapi_limits_balanced
 */
function template_preprocess_mcapi_limits_balanced(&$vars) {
  $currency = $vars['currency'];
  template_preprocess_mcapi_limits_absolute($vars);
  extract($vars['data']);
  $danger_zone_percent = 25;
  $balance_gauge_pixels = 120;
  $range = $max-$min;
  $zone_size = $range * $danger_zone_percent / 200;

  $min_safe = $min + $zone_size;
  $max_safe = $max - $zone_size;
  $id = $vars['id'] = $currency->id().'-ometer-'.$vars['account']->id();

  $vars['javascript'] = "
function drawGauge() {
  var data = google.visualization.arrayToDataTable([
    ['Label', 'Value'],
    ['". $currency->label() ."', $balance]
    ]);
    var options = {
    min: ". $min .",max: ". $max .",
    greenColor: '". $currency->color ."', greenFrom: ". $min .", greenTo: ". $min_safe .",
    yellowColor: '#ffffff', yellowFrom:". $min_safe .", yellowTo: ". $max_safe .",
    redColor: '". $currency->color ."', redFrom: ". $max_safe .", redTo: ". $max ."
};
new google.visualization.Gauge(document.getElementById('$id')).draw(data, options);
}
google.setOnLoadCallback(drawGauge);
";
  drupal_add_js('http://www.google.com/jsapi');
  drupal_add_js("google.load('visualization', '1', {packages: ['gauge']});", 'inline');
}


//this shows the amount available to spend or earn, by factoring in the user's balance
//rather than just showing the fixed limits
function limits_get_adjusted($currency, $account) {
  $summary = mcapi_account_summary($account, $currency);
  $limits = mcapi_limits_plugin($currency)->getLimits($account);
  $adjusted = array();
  if (isset($limits['min'])) $adjusted['spend_limit'] = $summary['balance'] - $limits['min'];
  if (isset($limits['max'])) $adjusted['earn_limit'] = $limits['max'] - $summary['balance'];
  return $adjusted;
}


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
  drupal_set_message('limits validation skipped until worths field is working'); return array();
  foreach ($cluster as $transaction) {
    foreach ($transaction->worths[0] as $currcode => $worth) {
      //we can't prepare the array in advance with zeros so += and -= throws notices
      //instead we just build up an array and add them up when we need them
      $diff[$transaction->payer->value][$currcode][] = -$worth->value;
      $diff[$transaction->payee->value][$currcode][] = $worth->value;
    }
  }
  return $diff;
}