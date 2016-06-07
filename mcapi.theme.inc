<?php

/**
 * @file
 * theme preprocessor functions
 */
use Drupal\mcapi\Mcapi;
use Drupal\mcapi\Entity\Currency;
use Drupal\Core\Render\Element;

function _mcapi_theme($existing, $type, $theme, $path) {
  $file = ['file' => 'mcapi.theme.inc'];//which actually this file
  $items['worths'] = [
    'variables' => [
      'values' => [],
      'separator' => ''
    ]
  ] + $file;
  $items['worth'] = [
    'render element' => 'worth'
  ] + $file;
  $items['mcapi_transaction'] = [
    'render element' => 'transaction',
    'template' => 'transaction'
  ] + $file;
  //the above is the theme wrapper for the below
  //so they both take the same render_element
  $items['certificate'] = [
    'render element' => 'transaction',
    'template' => 'certificate',
    'pattern' => 'certificate__'
  ] + $file;
  $items['mcapi_transaction_twig'] = [//experimental
    //when we call this we have to set the $transaction->twig property first
    'render element' => 'transaction',
    'function' => 'theme_mcapi_transaction_twig',
  ] + $file;
  $items['sentence'] = [
    'render element' => 'transaction'
  ] + $file;
  $items['mcapi_currency'] = [
    'render element' => 'currency',
    'template' => 'currency'
  ] + $file;
  $items['mcapi_timeline'] = [
    'variables' => [
      'title' => '',
      'width' => 0,
      'height' => 0,
      'points' => [],
      'currency' => NULL,///used for formatting
      'vaxislabels' => []//optional
    ]
  ];
  $wallet_callbacks = [
    'mcapi_wallet',
    'mcapi_wallet_component',
    'wallet_balances',
    'wallet_balance_bars',
    'wallet_stats',
  ];
  foreach ($wallet_callbacks as $callback) {
    $items[$callback] = [
      'render element' => 'element',
    ] + $file;
  }

  $items['mcapi_extreme_balances'] = [//this might just be a ul
    'variables' => [
      'largest' => 0,
      'tops' => [],
      'bottoms' => []
    ]
  ] + $file;
  $items['ordered_wallets'] = [
    'variables' => [
      'id' => '',
      'title' => '',
      'values' => [],
      'width' => 0,
      'height' => 0
    ]
  ] + $file;
  return $items;
}


/**
 * theme preprocessor for 'wallet_stats'
 */
function template_preprocess_wallet_stats(&$vars) {
  $wallet = $vars['element']['#wallet'];
  $unused = \Drupal::config('mcapi.settings')->get('render_unused');
  foreach ($wallet->getSummaries() as $curr_id => $data) {
    if ($unused || $data['trades']) {
      $currency = Currency::load($curr_id);
      $vars['currencies'][$curr_id]['label'] = $currency->label();
      $vars['currencies'][$curr_id]['gross_in'] = $currency->format($data['gross_in'], TRUE);
      $vars['currencies'][$curr_id]['gross_out'] = $currency->format($data['gross_out'], TRUE);
      $vars['currencies'][$curr_id]['volume'] = $currency->format($data['volume'], TRUE);
      $vars['currencies'][$curr_id]['balance'] = $currency->format($data['balance'], TRUE);
      $vars['currencies'][$curr_id]['partners'] = $data['partners'];
      $vars['currencies'][$curr_id]['trades'] = $data['trades'];
    }
  }
}

/**
 * default preprocessor for theme wrapper callback 'wallet_wrapper'
 */
function template_preprocess_mcapi_wallet(&$vars) {
  $wallet = $vars['element']['#mcapi_wallet'];
  if (Mcapi::maxWalletsOfBundle($wallet->getHolder()->getEntityTypeId(), $wallet->getHolder()->bundle()) > 1) {
    $vars['label'] = $wallet->label();
  }
  $vars['canonical'] = $wallet->toUrl('canonical')->toString();
  $vars['attributes'] = [
    'class' => "wallet ".$vars['element']['#view_mode']
  ];
}

/**
 * default preprocessor for theme wrapper callback 'wallet_wrapper'
 */
function template_preprocess_mcapi_wallet_component(&$vars) {
  $vars['title'] = $vars['element']['#title'];
}

/**
 * Theme preprocessor for all of an entity's wallets

 */
function template_preprocess_mcapi_wallets(&$vars) {
  $viewbuilder =\Drupal::entityTypeManager()->getViewBuilder('mcapi_wallet');
  //convert each wallet into a render array
  foreach ($vars['wallets'] as $key => $wallet) {
    $vars['wallets'][$key] = $viewbuilder->view($wallet, $vars['view_mode']);
  }
}

/**
 * theme preprocessor for balances
 */
function template_preprocess_wallet_balances(&$vars) {
  $unused = \Drupal::config('mcapi.settings')->get('render_unused');
  $wallet = $vars['element']['#wallet'];
  $vars['delimited'] = [];
  foreach ($wallet->getSummaries() as $curr_id => $data) {
    if ($unused || $data['trades']) {
      $currency = Currency::load($curr_id);
      $vars['currencies'][$curr_id]['label'] = $currency->label();
      $balance = $currency->format($data['balance'], Currency::DISPLAY_NORMAL);
      $vars['currencies'][$curr_id]['balance'] = strip_tags($balance);//@todo even better if we can let the html through
      $vars['delimited'][] = strip_tags($balance);
    }
  }
  $vars['delimited'] = implode(
    \Drupal::config('mcapi.settings')->get('worths_delimiter'),
    $vars['delimited']
  );
}

/**
 * theme preprocessor for balance_bars
 */
function template_preprocess_wallet_balance_bars(&$vars) {
  $wallet = $vars['element']['#wallet'];
  foreach ($wallet->getSummaries() as $curr_id => $data) {
    $currency = Currency::load($curr_id);
    //if ($vars['data']['gross_in'] == 0 && $vars['data']['gross_out']) return;
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
        ['value' => 0, 'label' => $currency->format(0, TRUE)],
        ['value' => $max / 2, 'label' => $currency->format(intval($max / 2), TRUE)],
        ['value' => $max, 'label' => $currency->format($max), TRUE]
      ]
    ];
  }
}

function _mcapi_get_axis_max($val) {
  $scale = [1, 2, 5, 10, 25, 50, 100, 250, 500, 1000, 2500, 10000, 25000, 50000, 100000, 250000, 500000, 1000000, 5000000];
  $scale[] = $val;
  sort($scale);
  return $scale[array_search($val, $scale) + 1];
}


/**
 * Implements template_preprocess_THEMEHOOK for theme callback 'mcapi_transaction'
 * DO NOT OVERRIDE, javascript transaction operations depend on the classes defined here
 * override certificate or change the mcapi_sentence variable instead
 */
function template_preprocess_mcapi_transaction(&$vars, $hook, $info) {
  $element = $vars['transaction'];
  $transaction = $element['#mcapi_transaction'];
  //don't know if this is needed in d8
  Element::setAttributes($element, ['class', 'id']);
  //if the view mode is not itself a template (matslats improvisation), include it as a class
  if (!strpos($element['#view_mode'], ' ')) {
    $vars['attributes']['class'][] = 'mode-'.$element['#view_mode'];
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
 * Implements template_preprocess_THEMEHOOK for theme callback 'mcapi_currency'
 *
 * @param array $vars
 * @todo there is no currency view page yet!
 */
function template_preprocess_mcapi_currency(&$vars) {
  $build = $vars['currency'];
  $currency = $build['#mcapi_currency'];
  $vars['name'] = $currency->name;
  $vars['owner'] = entity_load('user', $currency->uid)->toLink();
  $vars['color'] = $currency->color;
  $storage = \Drupal::entityTypeManager()->getStorage('mcapi_transaction');
  $vars['transactions'] = $storage->count($currency->id);
  $vars['volume'] = $storage->volume($currency->id);
  $vars['wallets'] = count($storage->wallets($currency->id));
}

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
  else $vars['children']  = '';
}

//experimental
function theme_mcapi_transaction_twig($vars) {
  $renderable = [
    '#type' => 'inline_template',
    '#template' => $vars['transaction']['#mcapi_transaction']->twig,
    '#context' => $vars
  ];
  return \Drupal::service('renderer')->render($renderable);
}

/**
 * Implements hook_preprocess_THEMEHOOK for wallet_histories
 * adds a column to the data with the formatted currency value
 */
function mcapi_preprocess_mcapi_timeline(&$vars) {
  $vars['id'] = preg_replace('/[^a-z0-9_]+/', '_', strtolower($vars['title']));

  foreach ($vars['points'] as $timestamp => $val) {
    $vars['daterows'][] = [
      date('Y,m,d', $timestamp),//format for javascript dates
      $val
    ];
  }
  //TEMP until gcharts works better. Force some formatted values on the axis
  if (isset($vars['currency']) &&  empty($vars['vaxislabels'])) {
    list($min, $middle, $max) = gchart_axes($vars['points']);
    $vars['vaxislabels'] = [
      [
        'value' => $min,
        'label' => $vars['currency']->format($min, Currency::DISPLAY_NORMAL, FALSE)
      ],
      [
        'value' => $middle,
        'label' => $vars['currency']->format($middle, Currency::DISPLAY_NORMAL, FALSE)
      ],
      [
        'value' => $max,
        'label' => $vars['currency']->format($max, Currency::DISPLAY_NORMAL, FALSE)
      ]
    ];
  }
  //@todo is there a way to have html labels in google charts?
  if ($vars['vaxislabels']) {
    foreach ($vars['vaxislabels'] as &$tick) {
      $tick['label'] = strip_tags($tick['label']);
    }
  }

}


/**
 * Calculate some good axis labels, using the min & max balance extents
 *
 * @param array $vals
 *   the $points history of balances keyed by unixtimes
 * @return array
 *   values keyed by min, 0 & max
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
