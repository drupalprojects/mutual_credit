<?php

/**
 * @file
 * Theme preprocessor functions.
 */
use Drupal\mcapi\Mcapi;
use Drupal\mcapi\Entity\Currency;
use Drupal\mcapi\Entity\CurrencyInterface;
use Drupal\Core\Render\Element;

/**
 * Theme preprocessor for 'wallet_stats'.
 */
function template_preprocess_wallet_stats(&$vars) {
  $wallet = $vars['element']['#wallet'];
  $unused = \Drupal::config('mcapi.settings')->get('render_unused');
  foreach ($wallet->getSummaries() as $curr_id => $data) {
    if ($unused || $data['trades']) {
      $currency = Currency::load($curr_id);
      $vars['currencies'][$curr_id]['label'] = $currency->label();
      $vars['currencies'][$curr_id]['gross_in'] = $currency->format($data['gross_in'], CurrencyInterface::DISPLAY_NORMAL);
      $vars['currencies'][$curr_id]['gross_out'] = $currency->format($data['gross_out'], CurrencyInterface::DISPLAY_NORMAL);
      $vars['currencies'][$curr_id]['volume'] = $currency->format($data['volume'], CurrencyInterface::DISPLAY_NORMAL);
      $vars['currencies'][$curr_id]['balance'] = $currency->format($data['balance'], CurrencyInterface::DISPLAY_NORMAL);
      $vars['currencies'][$curr_id]['partners'] = $data['partners'];
      $vars['currencies'][$curr_id]['trades'] = $data['trades'];
    }
  }
}

/**
 * Theme preprocessor for  'wallet_wrapper'.
 */
function template_preprocess_mcapi_wallet(&$vars) {
  $wallet = $vars['element']['#mcapi_wallet'];
  $holder = $wallet->getHolder();
  if (Mcapi::maxWalletsOfBundle($holder->getEntityTypeId(), $holder->bundle()) > 1) {
    $vars['label'] = $wallet->label();
  }
  $vars['canonical'] = $wallet->toUrl('canonical')->toString();
  $vars['attributes'] = [
    'class' => "wallet " . $vars['element']['#view_mode'],
  ];
}

/**
 * Theme preprocessor for 'wallet_wrapper'.
 */
function template_preprocess_mcapi_wallet_component(&$vars) {
  $vars['title'] = $vars['element']['#title'];
}

/**
 * Theme preprocessor for all of an entity's wallets.
 */
function template_preprocess_mcapi_wallets(&$vars) {
  $viewbuilder = \Drupal::entityTypeManager()->getViewBuilder('mcapi_wallet');
  // Convert each wallet into a render array.
  foreach ($vars['wallets'] as $key => $wallet) {
    $vars['wallets'][$key] = $viewbuilder->view($wallet, $vars['view_mode']);
  }
}

/**
 * Theme preprocessor for 'balances'.
 */
function template_preprocess_wallet_balances(&$vars) {
  $unused = \Drupal::config('mcapi.settings')->get('render_unused');
  $wallet = $vars['element']['#wallet'];
  $vars['delimited'] = [];
  foreach ($wallet->getSummaries() as $curr_id => $data) {
    if ($unused || $data['trades']) {
      $currency = Currency::load($curr_id);
      $vars['currencies'][$curr_id]['label'] = $currency->label();
      $balance = $currency->format($data['balance'], CurrencyInterface::DISPLAY_NORMAL);
      // @todo even better if we can let the html through
      $balance = strip_tags($balance->toString());
      $vars['currencies'][$curr_id]['balance'] = $balance;
      $vars['delimited'][] = $balance;
    }
  }
  $vars['delimited'] = implode(
    \Drupal::config('mcapi.settings')->get('worths_delimiter'),
    $vars['delimited']
  );
}

/**
 * Theme preprocessor for 'balance_bars'.
 */
function template_preprocess_wallet_balance_bars(&$vars) {
  $wallet = $vars['element']['#wallet'];
  foreach ($wallet->getSummaries() as $curr_id => $data) {
    $currency = Currency::load($curr_id);
    if (!$data['gross_in'] && !$data['gross_out']) {
      return;
    }
    $id = "given-received-" . $curr_id . '-' . $wallet->id();
    $max = _mcapi_get_axis_max(max($data['gross_in'], $data['gross_out']));
    $vars['currencies'][$curr_id] = [
      'label' => $currency->label(),
      'id' => $id,
      'functionname' => str_replace('-', '_', $id),
      'incoming' => $data['gross_in'],
      'outgoing' => $data['gross_out'],
      'show_in' => $currency->format($data['gross_in'], TRUE),
      'show_out' => $currency->format($data['gross_out'], TRUE),
      'given' => t('Given'),
      'received' => t('Received'),
      'max' => $max,
      'vaxislabels' => [
        [
          'value' => 0,
          'label' => strip_tags($currency->format(0, CurrencyInterface::DISPLAY_NORMAL)),
        ],
        [
          'value' => $max / 2,
          'label' => strip_tags($currency->format(intval($max / 2), CurrencyInterface::DISPLAY_NORMAL)),
        ],
        [
          'value' => $max,
          'label' => strip_tags($currency->format($max, CurrencyInterface::DISPLAY_NORMAL)),
        ],
      ],
    ];
  }
}

/**
 * Helper.
 *
 * @param int $val
 *   The max value to represent in the chart.
 */
function _mcapi_get_axis_max($val) {
  $scale = [1, 2, 5, 10, 25, 50, 100, 250, 500, 1000, 2500, 10000, 25000, 50000, 100000, 250000, 500000, 1000000, 5000000];
  $scale[] = $val;
  sort($scale);
  return $scale[array_search($val, $scale) + 1];
}

/**
 * Theme preprocessor for 'mcapi_transaction'.
 *
 * @note DO NOT OVERRIDE, javascript transaction operations depend on classes
 * defined herin. Override certificate or change the mcapi_sentence variable
 * instead.
 */
function template_preprocess_mcapi_transaction(&$vars, $hook, $info) {
  $element = $vars['transaction'];
  // don't know if this is needed in d8.
  Element::setAttributes($element, ['class', 'id']);
  // If the view mode is not itself a template (matslats improvisation), include
  // it as a class.
  if (!strpos($element['#view_mode'], ' ')) {
    $vars['attributes']['class'][] = 'mode-' . $element['#view_mode'];
  }
  if ($element['#view_mode'] == 'certificate') {
    foreach (['watermark', 'stamp'] as $overlay) {
      if (isset($element[$overlay]) && $element[$overlay]) {
        $vars['overlay'] = $element[$overlay];
        $vars['overlay_class'] = $overlay;
      }
    }
  }
  $vars['links'] = render($element['links']);
}

/**
 * Theme preprocessor for 'mcapi_transaction_twig'.
 */
function template_preprocess_mcapi_transaction_twig(&$vars) {
  $elements = $vars['transaction'];
  foreach (Element::children($elements) as $key) {
    $vars[$key] = $elements[$key];
  }
  $transaction = $elements['#mcapi_transaction'];
  $vars['state'] = $transaction->state->entity->label();
  $vars['type'] = $transaction->type->entity->label();
  $vars['creator'] = $transaction->creator->entity->toLink();
  if (!$transaction->isNew()) {
    $vars['url'] = $transaction->toLink();
  }
  if ($transaction->children) {
    $children = \Drupal::entityTypeManager()
      ->getViewBuilder('mcapi_transaction')
      ->viewMultiple($transaction->children, 'sentence');
    $vars['children'] = render($children);
  }
  else {
    $vars['children']  = '';
  }
}

/**
 * Experimental.
 */
function theme_mcapi_transaction_twig($vars) {
  $renderable = [
    '#type' => 'inline_template',
    '#template' => $vars['transaction']['#mcapi_transaction']->twig,
    '#context' => $vars,
  ];
  return \Drupal::service('renderer')->render($renderable);
}

/**
 * Theme preprocessor for 'mcapi_transaction_twig'.
 *
 * Adds a column to the data with the formatted currency value.
 */
function mcapi_preprocess_mcapi_timeline(&$vars) {
  $vars['id'] = preg_replace('/[^a-z0-9_]+/', '_', strtolower($vars['title']));
  foreach ($vars['points'] as $timestamp => $val) {
    $vars['daterows'][] = [
    // Format for javascript dates.
      date('Y,m,d', $timestamp),
      $val,
    ];
  }
  // TEMP until gcharts works better. Force some formatted values on the axis.
  if ($currency = $vars['currency']) {
    if ($currency->issuance == CurrencyInterface::TYPE_PROMISE) {
      // Make symmetrical axes for mutual credit.
      // @note its pretty difficult to give the axes nice limits if the native currency
      // val bears no resemblance to the display value.
      // @note this data is v specific to gCharts
      $max = _mcapi_get_axis_max(max(max($vars['points']), abs(min($vars['points']))));
      $vars['vaxislabels'] = [
        [
          'value' => -$max,
          'label' => $currency->format(-$max, CurrencyInterface::DISPLAY_NORMAL, FALSE),
        ],
        [
          'value' => 0,
          'label' => $currency->format(0, CurrencyInterface::DISPLAY_NORMAL, FALSE),
        ],
        [
          'value' => $max,
          'label' => $currency->format($max, CurrencyInterface::DISPLAY_NORMAL, FALSE),
        ],
      ];
    }
    else {
      list($min, $middle, $max) = gchart_axes($vars['points']);
      $vars['vaxislabels'] = [
        [
          'value' => $min,
          'label' => $currency->format($min, CurrencyInterface::DISPLAY_NORMAL, FALSE),
        ],
        [
          'value' => $middle,
          'label' => $currency->format($middle, CurrencyInterface::DISPLAY_NORMAL, FALSE),
        ],
        [
          'value' => $max,
          'label' => $currency->format($max, CurrencyInterface::DISPLAY_NORMAL, FALSE),
        ],
      ];
    }
  }
  // @todo is there a way to have html labels in google charts?
  if ($vars['vaxislabels']) {
    foreach ($vars['vaxislabels'] as &$tick) {
      $tick['label'] = strip_tags($tick['label']);
    }
  }
}

/**
 * Calculate some good axis labels, using the min & max balance extents.
 *
 * @param array $vals
 *   The $points history of balances keyed by unixtimes.
 *
 * @return array
 *   Values keyed by min, 0 & max.
 */
function gchart_axes($vals) {
  $max = max($vals);
  $min = min($vals);
  if ($min >= 0) {
    $max = _mcapi_get_axis_max($max);
    return [0, $max / 2, $max];
  }
  elseif ($max <= 0) {
    $min = -_mcapi_get_axis_max(abs($min));
    return [$min, $min / 2, 0];
  }
  else {
    return [-_mcapi_get_axis_max(abs($min)), 0, _mcapi_get_axis_max($max)];
  }
}

function template_preprocess_mcapi_currency(&$variables) {
  $currency = $variables['elements']['#mcapi_currency'];
  $stats = \Drupal::entityTypeManager()
    ->getStorage('mcapi_transaction')
    ->ledgerStateQuery($currency->id(), [])
    ->execute()
    ->fetch();
  $variables['name'] = $currency->label();
  $variables['issuance'] = Currency::issuances()[$currency->issuance];
  $variables['volume'] = $currency->format($stats->volume);
  $variables['trades'] = $stats->trades;
  $variables['wallets'] = $stats->partners;
  $variables['url'] = $currency->toUrl();
}

