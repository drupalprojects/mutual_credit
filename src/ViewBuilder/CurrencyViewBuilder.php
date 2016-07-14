<?php

namespace Drupal\mcapi\ViewBuilder;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityViewBuilder;
use \Drupal\mcapi\Entity\Wallet;
use \Drupal\mcapi\Entity\Currency;

/**
 * Visualisations of transactions per currency.
 */
class CurrencyViewBuilder extends EntityViewBuilder {

  /**
   * {@inheritdoc}
   */
  public function view(EntityInterface $entity, $view_mode = 'full', $langcode = NULL) {
    $currency = $entity;
    $build = parent::view($entity, $view_mode, $langcode);
    unset($build['#theme']);
    // @todo update this using entityTypemanager when the API changes maybe not before D9
    $transactionStorage  = $this->entityManager->getStorage('mcapi_transaction');

    $build['filter'] = [
      '#title' => $this->t('Filter'),
      '#type' => 'details',
      // @todo inject this
      'form' => \Drupal::formBuilder()->getForm('\Drupal\mcapi\Form\TransactionStatsFilterForm'),
      '#weight' => -1,
    ];

    // dsm(\Drupal::request()->query->all());
    $build['#cache_contexts'] = ['accounting'];
    // @todo sort out the classes/attributes
    $build['#attributes'] = ['class' => 'blah', 'id' => 'blah'];
    $build['#classes'] = ['blah'];
    $build['#id'] = ['blah'];

    $conditions = [
      'until' => REQUEST_TIME,
      'since' => $this->earliestTransactionTime(),
    ];

    //@todo support time periods
    list($from, $to, $period) = $this->periodQueryParams($conditions['since'], $conditions['until']);

    $balances_asc = $trades = $volumes = $incomes = $spending = [];
    foreach ($transactionStorage->ledgerStateByWallet($currency->id(), $conditions) as $row) {
      $balances_asc[$row->wid] = $row->balance;
      $trades[$row->wid] = $row->trades;
      $volumes[$row->wid] = $row->volume;
      $incomes[$row->wid] = $row->income;
      $spending[$row->wid] = $row->expenditure;
    }
    if (!$balances_asc) {
      return [
        '#markup' => t('No transactions yet', [], ['context' => 'accounting']),
      ];
    }

    asort($balances_asc);
    // Get the bottom wallets.
    for ($i = 0; $i < 10; $i++) {
      list($wid, $quant) = each($balances_asc);
      $wallet = Wallet::load($wid);
      if (!$wallet) {
        die('No wallet in \Drupal\mcapi\Element\ExtremeBalances');
      }
      $bottoms[] = [
        'raw' => abs($quant),
        'link' => $wallet->url(),
        'worth' => $currency->format($quant, Currency::DISPLAY_NORMAL, FALSE),
        'name' => $wallet->label(),
      ];
    }
    // Get the top wallets.
    $balances_desc = array_reverse($balances_asc, TRUE);
    for ($i = 0; $i < 10; $i++) {
      list($wid, $quant) = each($balances_desc);
      $wallet = Wallet::load($wid);
      $tops[] = [
        'raw' => $quant,
        'link' => $wallet->url(),
        'worth' => $currency->format($quant, Currency::DISPLAY_NORMAL, FALSE),
        'name' => $wallet->label(),
      ];
    }

    $build['balances_chart'] = [
    // Decide this.
      '#theme' => 'mcapi_extreme_balances',
      '#curr_id' => $entity->id(),
      '#depth' => 10,
      '#class' => ['extreme-balances'],
      '#largest'  => max(max($balances_desc), abs(min($balances_desc))),
      '#tops' => $tops,
      '#bottoms' => $bottoms,
      '#weight' => -1,
    ];

    $build['trades_per_user_wallet'] = [
      '#title' => t('Trades per wallet'),
      '#type' => 'mcapi_ordered_wallets',
      '#id' => 'trades_per_user_wallet_' . $entity->id(),
      '#curr_id' => $entity->id(),
      '#users_only' => TRUE,
      '#format_vals' => FALSE,
      '#data' => $trades,
      '#top' => 5,
    ];
    $build['volume_per_user_wallet'] = [
      '#title' => t('Volume per wallet'),
      '#type' => 'mcapi_ordered_wallets',
      '#id' => 'volume_per_user_wallet_' . $entity->id(),
      '#curr_id' => $entity->id(),
      '#users_only' => TRUE,
      '#format_vals' => TRUE,
      '#data' => $volumes,
      '#top' => 5,
    ];
    $build['income_per_user_wallet'] = [
      '#title' => t('Income per wallet'),
      '#type' => 'mcapi_ordered_wallets',
      '#id' => 'income_per_user_wallet_' . $entity->id(),
      '#curr_id' => $entity->id(),
      '#users_only' => TRUE,
      '#format_vals' => TRUE,
      '#data' => $incomes,
      '#top' => 5,
    ];
    $build['spending_per_user_wallet'] = [
      '#title' => t('Expenditure per wallet'),
      '#type' => 'mcapi_ordered_wallets',
      '#id' => 'spending_per_user_wallet_' . $entity->id(),
      '#curr_id' => $entity->id(),
      '#users_only' => TRUE,
      '#format_vals' => TRUE,
      '#data' => $spending,
      '#top' => 5,
    ];

    list($dates, $trades, $volumes, $wallets) = $transactionStorage->historyPeriodic($currency->id(), $period, $conditions);
    $params = [
      '@units' => $currency->label(),
      '@span' => strtolower(new TranslatableMarkup($period)),
    ];
    $build['trades_history'] = [
      '#theme' => 'mcapi_timeline',
      '#title' => t('Numbers of @units traded per @span', $params),
      '#points' => array_combine($dates, $trades),
      '#width' => 800,
      '#height' => 200,
    ];
    $build['participation_history'] = [
      '#theme' => 'mcapi_timeline',
      '#title' => t('Numbers of traders per @span', $params),
      '#points' => array_combine($dates, $wallets),
      '#width' => 800,
      '#height' => 200,
    ];
    $build['volume_history'] = [
      '#theme' => 'mcapi_timeline',
      '#title' => t('Volumes of @units traded per @span', $params),
      '#points' => array_combine($dates, $volumes),
      '#currency' => $currency,
      '#width' => 800,
      '#height' => 200,
    ];

    return $build;

  }

  /**
   * Get the time of the oldest transaction.
   *
   * Strictly speaking this function should be in the storage controller
   * interface and will cause problems with any transaction storage other than
   * mysql.
   */
  public function earliestTransactionTime() {
    $query = db_select('mcapi_transactions_index');
    $query->addExpression('MIN(created)');
    $query->condition('state', 'done');
    return $query->execute()->fetchField();
  }

  /**
   * Work out the start, end and frequency to poll the db.
   *
   * Less than a week, invalid.
   * Less than 100 days.
   * Less than 100 weeks, weekly.
   * Less than 100 months, monthly.
   * Otherwise annual.
   *
   * @param int $since
   *   A unix timestamp.
   * @param int $until
   *   A unix timestamp, larger than $since.
   */
  public function periodQueryParams($since, $until) {
    $span = $until - $since;
    $day = 86400;
    // $year = 31560192;
    if ($span < $day * 7) {
      // This won't produce very good results.
      $from = date('d-m-Y', $since);
      $to = date('d-m-Y', $until + $day);
      $groupby = 'Day';
    }
    // 100 days.
    elseif ($span < $day * 100) {
      $from = date('d-m-Y', $since);
      $to = date('d-m-Y', $until + $day);
      $groupby = 'Day';
    }
    // 100 weeks.
    elseif ($span < $day * 700) {
      // Get the beginning of the week.
      $from = date('Y', $since) . 'W' . date('W', $since);
      // The beginning of the week after the end.
      $to = date('Y', $since) . 'W' . date('W', $since) + 1;
      $groupby = 'Week';
    }
    // 100 months.
    elseif ($span < $day * 3000) {
      $from = date('Y', $since);
      // The beginning of the week after the end.
      $to = date('Y', $since) + 1;
      $groupby = 'Month';
    }
    else {
      $from  = date('Y', $since);
      $to = date('Y', $until) + 1;
      $groupby = 'Year';
    }
    return [strtotime($from), strtotime($to), $groupby];
    // Day, Week and Year are translated in Core.
    t('Week');
  }

}
