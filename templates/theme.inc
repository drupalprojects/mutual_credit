<?php

//@todo find a way to set the width of the chart using config
const GCHART_HISTORY_WIDTH = 300;

/**
 * @file
 * View and theme preprocessor functions
 */
use Drupal\mcapi\History;
use Drupal\mcapi\Entity\Currency;
use Drupal\mcapi\Entity\TransactionInterface;
use Drupal\mcapi\Entity\State;
use Drupal\Core\Render\Element;

/**
 * implements hook_preprocess_THEMEHOOK for wallet_histories
 * generates the javascript for the gchart from the user's history of each currency
 * 
 */
function mcapi_preprocess_wallet_histories(&$vars) {
  $element = $vars['element'];
  $wallet = $element['#wallet'];
  $vars['width'] = GCHART_HISTORY_WIDTH;
  $vars['height'] = $vars['width']/2;
  $histories = History::getAll(
    $wallet,
    $wallet->currenciesUsed(), 
    $vars['width']
  );
  foreach ($histories as $curr_id => $points) {
    if (count($points) < 2) {
      //don't draw the chart if it is empty
      continue;
    }
    $currency = Currency::load($curr_id);
    $vars['currencies'][$curr_id]['currency'] = $currency;
    $vars['currencies'][$curr_id]['functionname'] = 'drawHistory' . $curr_id;
    $vars['currencies'][$curr_id]['id'] = 'wallet-' . $wallet->id() . '-' . $curr_id;
    if ($points) {
      list($min, $middle, $max) = History::axes($points);
    }
    else {
      $min = -10;
      $middle = 0;
      $max = 10;
    }
    $vars['currencies'][$curr_id]['vaxislabels'] = [
      [
        'value' => $min, 
        'label' => $currency->format($min, TRUE)
      ],
      [
        'value' => $middle, 
        'label' => $currency->format($middle, TRUE)
      ],
      [
        'value' => $max, 
        'label' => $currency->format($max, TRUE)
      ]
    ];
    $vars['currencies'][$curr_id]['columns'] = [
      'date' => t('Date'),
      'number' => $currency->label()
    ];
    //populate the javascript data object
    foreach ($points as $timestamp => $balance) {
      //this has a resolution of one day, not very satisfying perhaps
      $vars['currencies'][$curr_id]['daterows'][] = [
        date('m/d/Y', $timestamp),
        $balance,
        $currency->format($balance, TRUE)
      ];
    }
  }
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
  $vars['label'] = $wallet->label();
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
 * theme preprocessor for balance_bars
 */
function template_preprocess_wallet_balance_bars(&$vars) {
  $wallet = $vars['element']['#wallet'];
  foreach ($wallet->getSummaries() as $curr_id => $data) {
    $currency = Currency::load($curr_id);
    //if ($vars['data']['gross_in'] == 0 && $vars['data']['gross_out']) return;
    $vars['currencies'][$curr_id]['label'] = $currency->label();
    $id = "given-gotten-" . $curr_id . '-' . $wallet->id();
    $vars['currencies'][$curr_id]['id'] = $id;
    $vars['currencies'][$curr_id]['functionname'] = str_replace('-', '_', $id);
    $vars['currencies'][$curr_id]['incoming'] = $data['gross_in'];
    $vars['currencies'][$curr_id]['outgoing'] = $data['gross_out'];
    $vars['currencies'][$curr_id]['show_in'] = $currency->format($data['gross_in'], TRUE);
    $vars['currencies'][$curr_id]['show_out'] = $currency->format($data['gross_out'], TRUE);
    $vars['currencies'][$curr_id]['given'] = t('Given');
    $vars['currencies'][$curr_id]['gotten'] = t('Gotten');
    $max = _mcapi_get_axis_max(max($data['gross_in'], $data['gross_out']));
    $vars['currencies'][$curr_id]['max'] = $max;
    $vars['currencies'][$curr_id]['vaxislabels'] = [
      ['value' => 0, 'label' => $currency->format(0, TRUE)],
      ['value' => $max / 2, 'label' => $currency->format(intval($max / 2), TRUE)],
      ['value' => $max, 'label' => $currency->format($max), TRUE]
    ];
  }
}

function _mcapi_get_axis_max($val) {
  $scale = [1, 2, 5, 10, 25, 50, 100, 250, 500, 1000, 2500, 10000, 25000, 50000, 100000, 250000, 500000, 1000000];
  $scale[] = $val;
  sort($scale);
  return $scale[array_search($val, $scale) + 1];
}


/**
 * implements template_preprocess_THEMEHOOK for theme callback 'mcapi_transaction'
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
 * implements template_preprocess_THEMEHOOK for theme callback 'certificate'
 *
 * @param array $vars
 */
function template_preprocess_certificate(&$vars) {
  $element = $vars['transaction'];
  $transaction = $element['#mcapi_transaction'];
  $vars = get_transaction_vars($transaction) + $vars;
  unset($element['worth'], $element['created']);//these are already rendered in $vars
  //hide the field api label
  foreach (Element::children($element) as $sub) {
    if (array_key_exists('#theme', $element[$sub]) && $element[$sub]['#theme'] == 'field') {
      $element[$sub]['#label_display'] = 'hidden';
    }
    $vars[$sub] = $element[$sub];
  }
  $vars['children'] = [];
  if (isset($transaction->children)) {
    foreach ($transaction->children as $child) {
      $vars['children'][$child->id()] = entity_view($child, 'sentence');
      //@todo there's something nasty happening with #pre_render being added here
      //pre_render connects to the display modes, which we don't want
      unset($vars['children'][$child->id()]['#pre_render']);
    }
  }
  //choose the transaction theme template based on the first currency of the transaction
  //not sure if this is worth the processing power at the moment
  //$vars['theme_hook_suggestions'][] = 'certificate__' . $transaction->get('type')->value;
}

/**
 * implements template_preprocess_THEMEHOOK for theme callback 'mcapi_currency'
 * 
 * @param array $vars
 * @todo there is no currency view page yet!
 */
function template_preprocess_mcapi_currency(&$vars) {
  $build = $vars['currency'];
  $currency = $build['#mcapi_currency'];
  $vars['name'] = $currency->name;
  $vars['owner'] = entity_load('user', $currency->uid)->link();
  $vars['color'] = $currency->color;
  $storage = \Drupal::entityTypeManager()->getStorage('mcapi_transaction');
  $vars['transactions'] = $storage->count($currency->id);
  $vars['volume'] = $storage->volume($currency->id);
  $vars['wallets'] = count($storage->wallets($currency->id));
}

/**
 * gets all the vars which we know are in the transaction
 */
function get_transaction_vars(TransactionInterface $transaction) {
  $vars['state'] = State::load($transaction->type->entity->start_state)->label;
  $vars['type'] = $transaction->type->entity->label;
  $vars['serial'] = $transaction->serial->value;
  foreach (['payer', 'payee'] as $trader) {
    $holder = $transaction->{$trader}->entity->getHolder();
    if ($holder->hasLinkTemplate('canonical')) {
      $vars[$trader] = $holder->link();
    }
    //if there is no canonical link to this entity just link to the wallet itself
    else {
      $vars[$trader] = $transaction->{$trader}->entity->link();
    }
  }
  $vars['creator'] = $transaction->creator->entity->link();
  //@todo do we need to sanitise this or does the theme engine do it? Test with a transaction
  $vars['description'] = $transaction->description->getString();

  //NB the transaction certificate, which uses entity_view_display overwrites field with display options, i.e. this one!
  //but this is needed for the sentence display'
  $worth = $transaction->worth->view(); 
  $worth['#label_display'] = 'hidden';
  $vars['worth'] = \Drupal::service('renderer')->render($worth);

  $vars['created'] = format_date($transaction->created->value, 'medium');

  //the token service lets you pass url options, but this function doesn't
  $vars['url'] = $transaction->url('canonical');
  return $vars;
}


