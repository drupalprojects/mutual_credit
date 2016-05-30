<?php

/**
 * @file
 * Contains \Drupal\mcapi\Element\McapiLedgerOverview.
 */

namespace Drupal\mcapi\Element;


/**
 * Provides a some visualisations showing the state of the system for one currency
 *
 * @RenderElement("mcapi_overview")
 */
class McapiLedgerOverview extends \Drupal\Core\Render\Element\RenderElement {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    return [
      '#pre_render' => [
        [get_class($this), 'preRender'],
      ],
      '#conditions' => [],
      '#since' => $this->earliestTransactionTime(),
    ];
  }

  /**
   * prerender callback
   */
  static function preRender($element) {
    $currency = \Drupal\mcapi\Entity\Currency::load($element['#curr_id']);
/*
    $query = \Drupal::entityQuery('mcapi_transaction')
      ->addTag('transactions_dashboard')
      ->condition('created', $element['#start'], '>')
      ->condition('created',  $element['#end'], '<');
    if (!empty($vals['type'])) {
      $query->condition('type', $vals['type']);
    }
    //add additional filters
    if (!empty($element['#conditions']['type'])) {
      $query->condition('type', $element['#conditions']['type']);
    }
    $txids = $query->execute();
*/

    $conditions = [
      'curr_id' => $element['#curr_id'],
      'until' => isset($element['#end']) ? $element['#end'] : REQUEST_TIME,
      'since' => $element['#since']
    ];
    $balances = $trades = $volumes = $incomes = $spending = [];
    $transactionStorage= \Drupal::entityTypeManager()->getStorage('mcapi_transaction');
    foreach ($transactionStorage->ledgerStateByWallet($conditions) as $row) {
      $balances[$row->wid] = $row->balance;
      $trades[$row->wid] = $row->trades;
      $volumes[$row->wid] = $row->volume;
      $incomes[$row->wid] = $row->income;
      $spending[$row->wid] = $row->expenditure;
    }
    if(!$balances) {
      return ['#markup' => $this->t('No transactions')];
    }
    $element['balances_chart'] = [
      '#type' => 'mcapi_balance_extremes',
      '#curr_id' => $element['#curr_id'],
      '#depth' => 10,
      '#data' => $balances,
    ];
    $element['trades_per_user_wallet'] = [
      '#title' => t('Trades per wallet'),
      '#type' => 'fieldset',
      'info' => [
        '#type' => 'mcapi_ordered_wallets',
        '#id' => 'trades_per_user_wallet_'.$element['#curr_id'],
        '#curr_id' => $element['#curr_id'],
        '#users_only' => TRUE,
        '#format_vals' => FALSE,
        '#data' => $trades,
        '#top' => 5,
      ]
    ];
    $element['volume_per_user_wallet'] = [
      '#title' => t('Volume per wallet'),
      '#type' => 'fieldset',
      'info' => [
        '#type' => 'mcapi_ordered_wallets',
        '#id' => 'volume_per_user_wallet_'.$element['#curr_id'],
        '#curr_id' => $element['#curr_id'],
        '#users_only' => TRUE,
        '#format_vals' => TRUE,
        '#data' => $volumes,
        '#top' => 5,
      ]
    ];
    $element['income_per_user_wallet'] = [
      '#title' => t('Income per wallet'),
      '#type' => 'fieldset',
      'info' => [
        '#type' => 'mcapi_ordered_wallets',
        '#id' => 'income_per_user_wallet_'.$element['#curr_id'],
        '#curr_id' => $element['#curr_id'],
        '#users_only' => TRUE,
        '#format_vals' => TRUE,
        '#data' => $incomes,
        '#top' => 5,
      ]
    ];
    $element['spending_per_user_wallet'] = [
      '#title' => t('Expenditure per wallet'),
      '#type' => 'fieldset',
      'info' => [
        '#type' => 'mcapi_ordered_wallets',
        '#id' => 'spending_per_user_wallet_'.$element['#curr_id'],
        '#curr_id' => $element['#curr_id'],
        '#users_only' => TRUE,
        '#format_vals' => TRUE,
        '#data' => $spending,
        '#top' => 5,
      ]
    ];

    list($from, $to, $period) = Self::periodQueryParams($conditions['since'], $conditions['until']);
    list($dates, $trades, $volumes, $wallets) = $transactionStorage->historyPeriodic($period, $conditions);
    $params = ['%units' => $currency->label(), '@span' => strtolower(new TranslatableMarkup($period))];
    $element['trades_history'] = [
      '#theme' => 'mcapi_timeline',
      '#title' => t('Numbers of %units traded per @span', $params),
      '#points' => array_combine($dates, $trades),
      '#width' => 800,
      '#height' => 200
    ];
    $element['participation_history'] = [
      '#theme' => 'mcapi_timeline',
      '#title' => t('Numbers of traders per %span', $params),
      '#points' => array_combine($dates, $wallets),
      '#width' => 800,
      '#height' => 200
    ];
    $element['volume_history'] = [
      '#theme' => 'mcapi_timeline',
      '#title' => t('Volumes of %units traded per @span', $params),
      '#points' => array_combine($dates, $volumes),
      '#currency' => $currency,
      '#width' => 800,
      '#height' => 200
    ];

    return $element;
  }

  /**
   * get the time of the oldest transaction
   * strictly speaking this function should be in the storage controller
   * interface and will cause problems with any transaction storage other than mysql
   */
  static function earliestTransactionTime() {
    $query = db_select('mcapi_transactions_index');
    $query->addExpression('MIN(created)');
    $query->condition('state',  'done');
    return $query->execute()->fetchField();
  }
/**
 * work out the start, end and frequency to poll the db
 * @param int $since
 *   a unix timestamp
 * @param int $until
 *   a unix timestamp, larger than $since
 *
 * less than a week, invalid
 * less than 100 days
 * less than 100 weeks, weekly
 * less than 100 months, monthly
 * otherwise annual
 */
  static function periodQueryParams($since, $until) {
    $span = $until - $since;
    $day = 86400;
    $year = 31560192;
    if ($span < $day*7) {
      drupal_set_message('Too short time $span to show analytics');
    }
    elseif($span < $day * 100) {//100 days
      $from = date('d-m-Y', $since);
      $to = date('d-m-Y', $until + $day);
      $groupby = 'Day';
    }
    elseif($span < $day * 700){//100 weeks
      //get the beginning of the week
      $from = date('Y', $since).'W'.date('W', $since);
      //the beginning of the week after the end
      $to = date('Y', $since).'W'.date('W', $since)+1;
      $groupby = 'Week';
    }
    elseif($span < $day * 3000) {//100 months
      $from = date('Y', $since);
      //the beginning of the week after the end
      $to = date('Y', $since)+1;
      $groupby = 'Month';
    }
    else {
      $from  = date('Y', $since);
      $to = date('Y', $until)+1;
      $groupby = 'Year';
    }
    return [strtotime($from), strtotime($to), $groupby];
    t('Week');//Day, Week and Year are translated in Core
  }
}
