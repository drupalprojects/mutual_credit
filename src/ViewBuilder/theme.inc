<?php

/**
 * @file
 * View and theme preprocessor functions
 */
use Drupal\mcapi\WalletInterface;
use Drupal\mcapi\CurrencyInterface;
use Drupal\mcapi\TransactionInterface;
use Drupal\mcapi\Entity\State;
use Drupal\Core\Render\Element;
use Drupal\Core\Url;

/*
 * get a renderable array of all the currency balances in one wallet
 *
 * @param WalletInterface $wallet
 *
 * @return []
 *   a render array
 */

function mcapi_balances(WalletInterface $wallet) {
  $renderable = array(
    '#theme' => 'wallet_balances',
    '#wallet' => $wallet,
    '#attached' => array(
      'library' => array('mcapi/mcapi.wallets', 'mcapi/mcapi.gchart'),
    )
  );
  foreach ($wallet->currenciesUsed() as $curr_id => $currency) {
    $renderable['#currencies'][$curr_id] = mcapi_balance_currency($wallet, $currency);
  }
  return $renderable;
}

/*
 * get a renderable array of one set of currency balances in one wallet
 *
 * @param WalletInterface $wallet
 *
 * @param CurrencyInterface $currency
 *
 * @return []
 *   a render array
 */

function mcapi_balance_currency(WalletInterface $wallet, CurrencyInterface $currency) {
  return array(
    '#theme' => 'wallet_summary_currency',
    '#wallet' => $wallet,
    '#currency' => $currency,
  );
}

/**
 * get a renderable array of all the trading histories in one wallet
 *
 * @param WalletInterface $wallet
 *
 * @return []
 *   a render array
 */
function mcapi_histories(WalletInterface $wallet) {
  $renderable = array(
    '#prefix' => '<div class = "wallet-histories">',
    '#suffix' => '</div>',
  );
  foreach ($wallet->currenciesUsed() as $curr_id => $currency) {
    $renderable[$curr_id] = array(
      '#theme' => 'wallet_history',
      '#wallet' => $wallet,
      '#currency' => $currency,
      '#width' => 250,
      '#attached' => array(
        'library' => array('mcapi/mcapi.wallets', 'mcapi/mcapi.gchart'),
      )
    );
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
  $renderable = array(
    '#prefix' => '<div class = "balance-bars">',
    '#suffix' => '</div>',
  );
  foreach ($wallet->getSummaries() as $curr_id => $data) {
    $renderable[$curr_id] = array(
      '#theme' => 'wallet_balance_bars',
      '#wallet' => $wallet,
      '#currency' => mcapi_currency_load($curr_id),
      '#data' => $data,
      '#attached' => array(
        'library' => array('mcapi/mcapi.wallets', 'mcapi/mcapi.gchart'),
      )
    );
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
  return array(
    '#theme' => 'wallet_stats', //this renders as css block (table) so doesn't sit well with the histories
    '#wallet' => $wallet,
    '#weight' => 5,
    '#attached' => array(
      'library' => array('mcapi/mcapi.wallets', 'mcapi/mcapi.gchart'),
    )
  );
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
 * TODO cache this and clear the cache
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
  $vars['vaxislabels'] = array(
    array('value' => $min, 'label' => $currency->format($min, TRUE)),
    array('value' => $middle, 'label' => $currency->format($middle, TRUE)),
    array('value' => $max, 'label' => $currency->format($max, TRUE))
  );
  $vars['columns'] = array(
    'date' => t('Date'),
    'number' => $currency->label()
  );

  //populate the javascript data object
  foreach ($vars['history'] as $timestamp => $balance) {
    //this has a resolution of one day, not very satisfying perhaps
    $vars['daterows'][] = array(
      date('m/d/Y', $timestamp),
      $balance,
      $currency->format($balance, TRUE)
    );
  }
}

/**
 * theme preprocessor for 'wallet_stats'
 * @todo this theme callback is cached - when to clear the cache and what cache is it?
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
 * theme preprocessor for 'wallet_balances'
 * show the most basic stats for one currency
 * @todo this theme callback is cached - when to clear the cache and what cache is it?
 */
function template_preprocess_wallet_balances(&$vars) {
  //TODO would be nice to sort the $vars['currencies'] in order of the number of trades
  //however can't do that because the currencies haven't been preprocessed yet
  //would need a quite different approach
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
 * show links to the wallet's local tasks
 *
 * @param WalletInterface $wallet
 *
 * @return []
 *   a render array
 */
function mcapi_wallet_links(WalletInterface $wallet) {
  $links = [];
  $tree = \Drupal::service('plugin.manager.menu.local_task')->getLocalTasksForRoute('entity.mcapi_wallet.canonical');
  $access_manager = \Drupal::service('access_manager');
  //which is all very well but getTasksBuild assumes we are on the current route
  foreach ($tree[0] as $child) {
    $route_name = $child->getRouteName();
    $route_parameters = [
      //TODO arg_0 can be removed when views args are working with the symfony router
      //'arg_0' => $wallet->id(),//this is for view.wallet_statement.page_1
      'mcapi_wallet' => $wallet->id()
    ];
    if ($access_manager->checkNamedRoute($route_name, $route_parameters, \Drupal::currentUser())) {
      $links[$route_name] = array(
        'title' => $child->getTitle(),
        'url' => Url::fromRoute($route_name, $route_parameters)
      );
    }
  }
  return array(
    '#theme' => 'links',
    '#links' => $links,
  );
}

/**
 *
 */
function template_preprocess_wallet_summary_currency(&$vars) {
  $vars['href'] = 'wallet/' . $vars['wallet']->id() . '/inex/' . $vars['currency']->id(); //TODO use \Drupal::url()
  $vars += $vars['wallet']->getStats($vars['currency']->id());
  $vars['balance'] = $vars['currency']->format($vars['balance'], TRUE);
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
  $vars['vaxislabels'] = array(
    array('value' => 0, 'label' => $currency->format(0, TRUE)),
    array('value' => $max / 2, 'label' => $currency->format($max / 2, TRUE)),
    array('value' => $max, 'label' => $currency->format($max), TRUE)
  );
}

function _mcapi_history_axes($vals) {
  $max = max($vals);
  $min = min($vals);
  if ($min >= 0) {
    $max = _mcapi_get_axis_max($max);
    return array(0, $max / 2, $max);
  }
  elseif ($max <= 0) {
    $min = -_mcapi_get_axis_max(abs($min));
    return array($min, $min / 2, 0);
  }
  else {
    return array(-_mcapi_get_axis_max(abs($min)), 0, mcapi_get_axis_max($max));
  }
}

function _mcapi_get_axis_max($val) {
  $scale = array(1, 2, 5, 10, 25, 50, 100, 250, 500, 1000, 2500, 10000, 25000, 50000, 100000, 250000, 500000, 1000000);
  $scale[] = $val;
  sort($scale);
  return $scale[array_search($val, $scale) + 1];
}

/**
 * theme implementation function for mcapi_wallets
 */
function template_preprocess_mcapi_wallets(&$vars) {
  //are the wallets aready themed?
//  if (!is_null($vars['wallets']))mtrace();
  foreach ($vars['wallets'] as $wid => $wallet) {
    $vars['wallets'][$wid] = \Drupal::service('renderer')->render($wallet);
  }
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
  //TODO is noLinks really necessary
  if (!$transaction->noLinks) {
    $vars['links'] = \Drupal::Entitymanager()
      ->getViewBuilder('mcapi_transaction')
      ->renderlinks($transaction, $element['#view_mode']);
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
  process_transaction_vars($vars, $transaction);
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
      //TODO there's something nasty happening with #pre_render being added here
      //pre_render connects to the display modes, which we don't want
      unset($vars['children'][$child->id()]['#pre_render']);
    }
  }

  //choose the transaction theme template based on the first currency of the transaction
  //not sure if this is worth the processing power at the moment
  $vars['theme_hook_suggestions'][] = 'certificate__' . $transaction->get('type')->value;
}

/**
 * theme implementation for callback 'sentence'
 * to show the transaction in one line.
 *
 * @todo remove this in favour of TransactionViewBuilder
 */
function theme_sentence(&$vars) {
  die('theme_sentence()'); //this shouldn't be used
  $element = $vars['transaction'];
  return \Drupal::Token()->replace(
      \Drupal::config('mcapi.misc')->get('sentence_template'), array('mcapi' => $element['#mcapi_transaction']), array('sanitize' => TRUE)
  );
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
  //$vars['exchanges'] = $currency->created;//TODO put this in an alter hook
}

/**
 * Prepares a transaction, and renders it using a passed twig template
 *
 * @param string $template
 * @param TransactionInterface $transaction
 *
 * @todo perhaps convert this theme function to use
 * https://api.drupal.org/api/drupal/core!vendor!twig!twig!lib!Twig!Extension!StringLoader.php/function/twig_template_from_string/8
 * New in D8 alpha12
 */
function render_twig_transaction($template, TransactionInterface $transaction) {
  $vars = [];
  $xid = $transaction->id();
  //Here we have to get all the fieldAPI fields rendered and in $vars
  $entities = array($xid => $transaction);
  $build = \Drupal::entityManager()
    ->getViewBuilder('mcapi_transaction')
    ->viewMultiple($entities, 'certificate');

  //hide the labels from the standard field rendering
  foreach (Element::children($build[$xid]) as $sub) {
    if ($build[$xid][$sub]['#theme'] == 'field') {
      $build[$xid][$sub]['#label_display'] = 'hidden';
    }
    $vars[$sub] = $build[$xid][$sub];
  }
  return \Drupal::service('twig')->renderInline(
    $template,
    process_transaction_vars($vars, $transaction)
  );
}

/**
 * {@inheritdoc}
 */
function process_transaction_vars(array &$vars, TransactionInterface $transaction) {
  $v['state'] = State::load($transaction->type->entity->start_state)->label;
  $v['type'] = $transaction->type->entity->label;
  $v['serial'] = $transaction->serial->value;
  foreach (['payer', 'payee'] as $trader) {
    $owner = $transaction->{$trader}->entity->getOwner();
    if ($owner->hasLinkTemplate('canonical')) {
      $v[$trader] = $owner->link();
    }
    //if there is no canonical link to this entity just link to the wallet itself
    else {
      $v[$trader] = $transaction->{$trader}->entity->link();
    }
  }
  $v['creator'] = $transaction->creator->entity->link();
  //TODO do we need to sanitise this or does the theme engine do it?
  $v['description'] = $transaction->description->getString();

  //NB the transaction certificate, which uses entity_view_display overwrites field with display options, i.e. this one!
  //but this is needed for the sentence display
  $v['worth'] = $transaction->worth->view();

  //TODO LEAVE THIS TILL AFTER alpha 11
  $v['created'] = format_date($transaction->created->value, 'medium');

  //the token service lets you pass url options, but this function doesn't
  $v['url'] = $transaction->url('canonical');
  $vars += $v;
}
