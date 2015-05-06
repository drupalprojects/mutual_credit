<?php

/**
 * @file
 * View and theme preprocessor functions
 */
use Drupal\mcapi\Wallet;
use Drupal\mcapi\WalletInterface;
use Drupal\mcapi\CurrencyInterface;
use Drupal\mcapi\TransactionInterface;
use Drupal\mcapi\Entity\State;
use Drupal\Core\Render\Element;
use Drupal\Core\Url;
use Drupal\Core\Template\Attribute;
use Drupal\Component\Serialization\Json;

/**
 * get a renderable array of all the trading histories in one wallet
 *
 * @param WalletInterface $wallet
 *
 * @return []
 *   a render array
 */
function mcapi_histories(WalletInterface $wallet) {
  $renderable = [
    '#prefix' => '<div class = "wallet-histories">',
    '#suffix' => '</div>',
  ];
  foreach ($wallet->currenciesUsed() as $curr_id => $currency) {
    $renderable[$curr_id] = [
      '#theme' => 'wallet_history',
      '#wallet' => $wallet,
      '#currency' => $currency,
      '#width' => 250,
      '#attached' => ['mcapi/mcapi.gchart']
    ];
  }
  return $renderable;
}

/**
 * get a renderable array of all the balance bar charts in one wallet
 *
 * @param WalletInterface $wallet
 *
 * @return []
 *   a render array
 */
function mcapi_balance_bars(WalletInterface $wallet) {
  $renderable = [
    '#prefix' => '<div class = "balance-bars">',
    '#suffix' => '</div>',
  ];
  foreach ($wallet->getSummaries() as $curr_id => $data) {
    $renderable[$curr_id] = [
      '#theme' => 'wallet_balance_bars',
      '#wallet' => $wallet,
      '#currency' => mcapi_currency_load($curr_id),
      '#data' => $data
    ];
  }
  return $renderable;
}

/**
 * @param WalletInterface $wallet
 *
 * @return []
 *   a render array
 */
function mcapi_stats(WalletInterface $wallet) {
  return [
    '#theme' => 'wallet_stats', //this renders as css block (table) so doesn't sit well with the histories
    '#wallet' => $wallet,
    '#weight' => 5,
  ];
}

/**
 * implements template_preprocess_THEME
 * modify the history points for aesthetic reasons, or to save resources
 */
function template_preprocess_wallet_history(&$vars) {
  $storage = \Drupal::entityManager()->getStorage('mcapi_transaction');
  if ($history = $storage->timesBalances($vars['wallet']->id(), $vars['currency']->id())) {
    //add a final point showing the balance at this moment
    $history[REQUEST_TIME] = end($history);
  }
  //apply smoothing, or even roughing.
  $point_count = count($history);
  if ($point_count < $vars['width'] / 3) {//step method, for a small number of points
    $times = $values = [];
    //make two values for each one in the keys and values
    foreach ($history as $time => $bal) {
      $times[] = $time;
      $times[] = $time + 1;
      $values[] = $bal;
      $values[] = $bal;
    }
    //now slide the arrays against each other to create steps
    array_pop($values);
    array_shift($times);
    $history = array_combine($times, $values);
  }
  elseif ($point_count > $vars['width']) {//decimate the array, for a large number of points
    //we can assume that $max_size(1 point per pixes) is smaller than $count
    $ratio = $vars['width'] / $point_count;
    //how to turn this into a fraction involving 1?
    $reciprocal = 1 / $ratio;
    $factor = intval($reciprocal + 1);
    //now iterate through the array taking 1 out of every $factor values
    $i = 0;
    foreach ($history as $key => $value) {
      if ($i % $factor != 0)
        unset($history[$key]);
      $i++;
    }
  }
  $vars['history'] = $history;
}

/**
 * implements hook_preprocess_THEMEHOOK for wallet_history
 * generates the javascript for the gchart from the user's history of each currency
 */
function mcapi_preprocess_wallet_history(&$vars) {
  $currency = $vars['currency'];
  $vars['height'] = $vars['width'] * 3 / 4;
  $vars['functionname'] = 'drawHistory' . $currency->id();

  $vars['id'] = 'wallet-' . $vars['wallet']->id() . '-' . $currency->id();
  if ($vars['history']) {
    list($min, $middle, $max) = _mcapi_history_axes($vars['history']);
  }
  else {
    $min = -100;
    $middle = 0;
    $max = 100;
  }
  $vars['vaxislabels'] = [
    ['value' => $min, 'label' => $currency->format($min, TRUE)],
    ['value' => $middle, 'label' => $currency->format($middle, TRUE)],
    ['value' => $max, 'label' => $currency->format($max, TRUE)]
  ];
  $vars['columns'] = [
    'date' => t('Date'),
    'number' => $currency->label()
  ];

  //populate the javascript data object
  foreach ($vars['history'] as $timestamp => $balance) {
    //this has a resolution of one day, not very satisfying perhaps
    $vars['daterows'][] = [
      date('m/d/Y', $timestamp),
      $balance,
      $currency->format($balance, TRUE)
    ];
  }
}

/**
 * theme preprocessor for 'wallet_stats'
 */
function template_preprocess_wallet_stats(&$vars) {
  foreach ($vars['wallet']->getSummaries() as $curr_id => $data) {
    $currency = mcapi_currency_load($curr_id);
    $vars['currdata'][$curr_id]['label'] = $currency->label();
    $vars['currdata'][$curr_id]['gross_in'] = $currency->format($data['gross_in'], TRUE);
    $vars['currdata'][$curr_id]['gross_out'] = $currency->format($data['gross_out'], TRUE);
    $vars['currdata'][$curr_id]['volume'] = $currency->format($data['volume'], TRUE);
    $vars['currdata'][$curr_id]['balance'] = $currency->format($data['balance'], TRUE);
    $vars['currdata'][$curr_id]['partners'] = $data['partners'];
    $vars['currdata'][$curr_id]['trades'] = $data['trades'];
  }
}

/**
 *
 * @param type $vars
 */
function template_preprocess_wallet_wrapper(&$vars) {
  $vars['label'] = $vars['element']['#mcapi_wallet']->label();
  $links = mcapi_wallet_links($vars['element']['#mcapi_wallet']);
  $vars['links'] = \Drupal::service('renderer')->render($links);
}

/**
 * re-present a wallet's local tasks as an set of links
 *
 * @param WalletInterface $wallet
 *
 * @return []
 *   a render array
 *
 * @todo when is mcapi_wallet_links() this used? Does it work?
 */
function mcapi_wallet_links(WalletInterface $wallet) {
  $links = [];
  $tree = \Drupal::service('plugin.manager.menu.local_task')
    ->getLocalTasksForRoute('entity.mcapi_wallet.canonical');
  $access_manager = \Drupal::service('access_manager');
  //which is all very well but getTasksBuild assumes we are on the current route
  foreach ($tree[0] as $child) {
    $route_name = $child->getRouteName();
    $route_parameters = [
      //arg_0 can be removed when views args are working with the symfony router
      //'arg_0' => $wallet->id(),//this is for view.wallet_statement.page_1
      'mcapi_wallet' => $wallet->id()
    ];
    if ($access_manager->checkNamedRoute($route_name, $route_parameters, \Drupal::currentUser())) {
      $links[$route_name] = [
        'title' => $child->getTitle(),
        'url' => Url::fromRoute($route_name, $route_parameters)
      ];
    }
  }
  return [
    '#theme' => 'links',
    '#links' => $links,
  ];
}

/**
 * Preprocessor for all of an entity's wallets
 *
 * @param type $vars
 *
 */
function template_preprocess_mcapi_wallets(&$vars) {
  $viewbuilder =\Drupal::entityManager()->getViewBuilder('mcapi_wallet');
  //convert each wallet into a render array
  foreach ($vars['wallets'] as $key => $wallet) {
    $vars['wallets'][$key] = $viewbuilder->view($wallet, $vars['view_mode']);
  }
  if (Drupal::config('mcapi.wallets')->get('add_link_location')['summaries']) {
    //show the add new wallet link
    $route_name = 'mcapi.wallet.add.'.$vars['owner']->getEntityTypeId();
    $params = ['user' => $vars['owner']->id()];
    //how do we do this utilising the cached value?
    $access = \Drupal::service('access_manager')
      ->checkNamedRoute($route_name, $params, \Drupal::currentUser());
    //echo $access;die();
    if ($access) {
      $vars['add_wallet_link'] = [
        '#type' => 'link',
        '#title' => t('Add wallet'),
        '#url' => Url::fromRoute($route_name, $params),
        '#weight' => 2,
        '#options' => [
          'attributes' => new Attribute([
            'class' => ['add-wallet-link']
          ])
        ],
        //this gets around the default css inline-block with which css 'clear' doesn't seem to work
        //'#prefix' => '<br />'
      ];
      //print_r($vars['add_wallet_link']['#url']);
      \Drupal::service('renderer')->render($vars['add_wallet_link']);//@todo is this auto-rendered later?
    }
  }
}

/**
 * theme preprocessor for wallet_balance_bars
 */
function template_preprocess_wallet_balance_bars(&$vars) {
  $data = $vars['data'];
  $currency = $vars['currency'];
  //if ($vars['data']['gross_in'] == 0 && $vars['data']['gross_out']) return;
  $vars['id'] = "given-gotten-" . $currency->id() . '-' . $vars['wallet']->id();
  $vars['functionname'] = str_replace('-', '_', $vars['id']);
  $vars['incoming'] = $data['gross_in'];
  $vars['outgoing'] = $data['gross_out'];
  $vars['show_in'] = $currency->format($data['gross_in'], TRUE);
  $vars['show_out'] = $currency->format($data['gross_out'], TRUE);
  $vars['given'] = t('Given');
  $vars['gotten'] = t('Gotten');
  $max = _mcapi_get_axis_max(max($data['gross_in'], $data['gross_out']));
  $vars['max'] = $max;
  $vars['vaxislabels'] = [
    ['value' => 0, 'label' => $currency->format(0, TRUE)],
    ['value' => $max / 2, 'label' => $currency->format($max / 2, TRUE)],
    ['value' => $max, 'label' => $currency->format($max), TRUE]
  ];
}

function _mcapi_history_axes($vals) {
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
    return [-_mcapi_get_axis_max(abs($min)), 0, mcapi_get_axis_max($max)];
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
 * DO NOT OVERRIDE, javascript transitions depend on the classes defined here
 * override certificate or change the mcapi_sentence variable instead
 */
function template_preprocess_mcapi_transaction(&$vars) {
  $element = $vars['transaction'];
  $transaction = $element['#mcapi_transaction'];
  //don't know if this is needed in d8
  Element::setAttributes($element, ['class', 'id']);
  $vars['attributes']['class'] = [
    'transaction',
    'type-'.$transaction->type->target_id,
    'state-' . $transaction->state->target_id
  ];
  //if the view mode is not itself a template (matslats improvisation), include it as a class
  if (!strpos($element['#view_mode'], ' ')) {
    $vars['attributes']['class'][] = 'mode-'.$element['#view_mode'];
  }

  foreach (['watermark', 'stamp'] as $overlay) {
    if (@$element[$overlay]) {
      $vars['overlay'] = $element[$overlay];
      $vars['overlay_class'] = $overlay;
    }
  }
  //@todo consider other approaches rather than the noLinks undeclared variable
  if (!$transaction->noLinks) {
    $vars['links'] = \Drupal::service('mcapi.transitions')
      ->getLinks($transaction, $element['#view_mode']);
  }
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


/*
 * - $currency: Full mcapi_transaction entity
 * - $name: name of the currency (probably used in the page title)
 * - $created: unixtime of creation date.
 * - $owner: linked username of owner
 * - $color: hex code
 * and a range of usage stats yet to be determined
 * - $transactions: the number of transactions using this currency
 * - $wallets: the number of wallets with this currency
 * - $exchanges: a list of links to exchanges (added by exchanges module)
 */

function template_preprocess_mcapi_currency(&$vars) {
  $build = $vars['currency'];
  $currency = $build['#mcapi_currency'];
  $vars['name'] = $currency->name;
  $vars['owner'] = entity_load('user', $currency->uid)->link();
  $vars['color'] = $currency->color;
  $storage = \Drupal::entityManager()->getStorage('mcapi_transaction');
  $vars['transactions'] = $storage->count($currency->id);
  $vars['volume'] = $storage->volume($currency->id);
  $vars['wallets'] = count($storage->wallets($currency->id));
}

/**
 * {@inheritdoc}
 */
function get_transaction_vars(TransactionInterface $transaction) {
  $vars['state'] = State::load($transaction->type->entity->start_state)->label;
  $vars['type'] = $transaction->type->entity->label;
  $vars['serial'] = $transaction->serial->value;
  foreach (['payer', 'payee'] as $trader) {
    $owner = $transaction->{$trader}->entity->getOwner();
    if ($owner->hasLinkTemplate('canonical')) {
      $vars[$trader] = $owner->link();
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
  //but this is needed for the sentence display
  $vars['worth'] = $transaction->worth->view();

  $vars['created'] = format_date($transaction->created->value, 'medium');

  //the token service lets you pass url options, but this function doesn't
  $vars['url'] = $transaction->url('canonical');
  return $vars;
}
