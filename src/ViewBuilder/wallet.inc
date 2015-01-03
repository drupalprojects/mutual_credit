<?php 

use Drupal\mcapi\WalletInterface;
use Drupal\mcapi\CurrencyInterface;
use Drupal\Core\Url;

//delete this
function mcapi_links() {
 return [];
}
//delete this
function mcapi_name() {
 return [];
}
//delete this
function mcapi_mcapi_limits_absolute() {
 return [];
}

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
  foreach ($wallet->currencies_used() as $curr_id => $currency) {
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
  foreach ($wallet->currencies_used() as $curr_id => $currency) {
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
    '#theme' => 'wallet_stats',//this renders as css block (table) so doesn't sit well with the histories
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
    $times = $values = array();
    //make two values for each one in the keys and values
    foreach ($history as $time => $bal) {
      $times[] = $time;
      $times[] = $time+1;
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
    $reciprocal = 1/$ratio;
    $factor = intval($reciprocal + 1);
    //now iterate through the array taking 1 out of every $factor values
    $i = 0;
    foreach($history as $key => $value) {
      if ($i % $factor != 0) unset($history[$key]);
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
  $vars['height'] = $vars['width']*3/4;
  $vars['functionname'] = 'drawHistory'.$currency->id();

  $vars['id'] = 'wallet-'.$vars['wallet']->id().'-'.$currency->id();
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
  $vars['links'] = drupal_render($links);
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
  $links= [];
  $tree = \Drupal::service('plugin.manager.menu.local_task')->getLocalTasksForRoute('mcapi.wallet_view');
  $access_manager = \Drupal::service('access_manager');
  //which is all very well but getTasksBuild assumes we are on the current route
  foreach($tree[0] as $child){
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
  $vars['href'] = 'wallet/'.$vars['wallet']->id().'/inex/'.$vars['currency']->id();//TODO use \Drupal::url()
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
  $vars['id'] = "given-gotten-".$currency->id().'-'.$vars['wallet']->id();
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
    array('value' => $max/2, 'label' => $currency->format($max/2, TRUE)),
    array('value' => $max, 'label' => $currency->format($max), TRUE)
  );

}


function _mcapi_history_axes($vals) {
  $max = max($vals);
  $min = min($vals);
  if ($min >= 0) {
    $max = _mcapi_get_axis_max($max);
    return array(0, $max/2, $max);
  }
  elseif ($max <= 0) {
    $min = -_mcapi_get_axis_max(abs($min));
    return array($min, $min/2, 0);
  }
  else {
    return array(-_mcapi_get_axis_max(abs($min)), 0, mcapi_get_axis_max($max));
  }
}


function _mcapi_get_axis_max($val) {
  $scale = array(1, 2, 5, 10, 25, 50, 100, 250, 500, 1000, 2500, 10000, 25000, 50000, 100000, 250000, 500000, 1000000);
  $scale[] = $val;
  sort($scale);
  return $scale[array_search($val, $scale)+1];
}



