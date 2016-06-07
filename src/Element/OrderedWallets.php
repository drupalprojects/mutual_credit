<?php

/**
 * @file
 * Contains \Drupal\mcapi\Element\OrderedWallets.
 */

namespace Drupal\mcapi\Element;

use \Drupal\mcapi\Entity\Currency;


/**
 * A chart showing all the wallets lined up in some order, and a table showing the extremes
 *
 * @RenderElement("mcapi_ordered_wallets")
 */
class OrderedWallets extends \Drupal\Core\Render\Element\Fieldset {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    $info = parent::getInfo();
    $info['#pre_render'][] = ['\Drupal\mcapi\Element\OrderedWallets', 'preRender'];
    $info += [
      '#depth' => 10,
      '#users_only' => TRUE,
      '#format_vals' => FALSE,
      '#top' => 5,//show the rankings
      '#attributes' => ['class' => ['ordered-wallets']]
    ];
    return $info;
  }

  /**
   * prerender callback
   */
  static function preRender($element) {
    static $i=0;
    asort($element['#data']);
    $currency = Currency::load($element['#curr_id']);
    $element['chart'] = [
      '#theme' => 'ordered_wallets',
      '#id' => 'ordered_wallets_'. $currency->id( ).'_'. $i++,
      ///'#title' => $element['#title'],
      '#values' => $element['#data'],
      '#width' => 200,
      '#height' => 125,//any smaller and gChart axis labels don't show
      '#class' => ['ordered-wallets']
    ];
    if ($element['#format_vals']) {
      $tick = Self::getTick($element['#data']);
      $element['chart']['#vticks'][$tick] =  $currency->format($tick, Currency::DISPLAY_NORMAL, FALSE);
    }
    if (!empty($element['#top'])) {
      $element['top'] = [
        '#theme' => 'item_list',
        '#list_type' => 'ol',
        '#items' => []
      ];
      //@todo put titles on list items "See record for %name"
      $data = array_reverse($element['#data'], TRUE);
      while (count($element['top']['#items']) < $element['#top']) {
        list($wid, $val) = each($data);
        $wallet = \Drupal\mcapi\Entity\Wallet::load($wid);
        if ($element['#users_only'] && $wallet->holder_entity_type->value != 'user') {
          continue;
        }
        if ($element['#format_vals']) {
          $val = $currency->format($val, Currency::DISPLAY_NORMAL, FALSE);
        }
        $element['top']['#items'][] = ['#markup' => $wallet->getHolder()->toLink()->toString() . ' ('.$val.')'];
      }
    }
    return $element;
  }

  static function getTick($vals) {
    $max = max($vals);
    $tick = str_pad(1, strlen($max), '0');
    $val = 0;
    while ($val < $max-$tick) {
      $val += $tick;
    }
    return $val;
  }
}
