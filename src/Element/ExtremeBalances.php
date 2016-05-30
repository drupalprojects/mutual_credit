<?php

/**
 * @file
 * Contains \Drupal\mcapi\Element\ExtremeBalances.
 */

namespace Drupal\mcapi\Element;

use \Drupal\mcapi\Entity\Wallet;


/**
 * A chart showing all the wallets lined up in some order, and a table showing the extremes
 *
 * @RenderElement("mcapi_balance_extremes")
 */
class ExtremeBalances extends \Drupal\Core\Render\Element\RenderElement {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    return [
      '#pre_render' => [
        ['\Drupal\mcapi\Element\ExtremeBalances', 'preRender'],
      ],
      '#depth' => 10,
      //'#data' => [],//required
      //'#curr_id' => '',//required
    ];
  }

  /**
   * prerender callback
   */
  static function preRender($element) {
    asort($element['#data']);
    $currency = \Drupal\mcapi\Entity\Currency::load($element['#curr_id']);
    for ($i = 0; $i < $element['#depth']; $i++) {
      list($wid, $quant) = each($element['#data']);
      $wallet = Wallet::load($wid);
      if(!$wallet) {
        die('No wallet in \Drupal\mcapi\Element\ExtremeBalances');
      }
      $bottoms[] = [
        'raw' => abs($quant),
        'link' => $wallet->url(),
        'worth' => $currency->format($quant),
        'name' => $wallet->label()
      ];
    }
    $data = array_reverse($element['#data'], TRUE);
    for ($i = 0; $i < $element['#depth']; $i++) {
      list($wid, $quant) = each($data);
      $wallet = Wallet::load($wid);
      $tops[] = ['raw' => $quant, 'link' => $wallet->url(), 'worth' => $currency->format($quant), 'name' => $wallet->label()];
    }

    $element['chart'] = [
      '#theme' => 'mcapi_extreme_balances',//decide this
      '#largest'  => max(max($data), abs(min($data))),
      '#tops' => $tops,
      '#bottoms' => $bottoms
    ];
    return $element;

  }

}
