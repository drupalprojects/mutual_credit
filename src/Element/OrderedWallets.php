<?php

namespace Drupal\mcapi\Element;

use Drupal\mcapi\Entity\Wallet;
use Drupal\Core\Render\Element\Fieldset;
use \Drupal\mcapi\Entity\Currency;

/**
 * Show all the wallets ordered somehow, + list showing the most and/or least.
 *
 * @RenderElement("mcapi_ordered_wallets")
 */
class OrderedWallets extends Fieldset {

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
    // Show the rankings.
      '#top' => 5,
      '#attributes' => ['class' => ['ordered-wallets']],
    ];
    return $info;
  }

  /**
   * Prerender callback.
   */
  public static function preRender($element) {
    static $i = 0;
    asort($element['#data']);
    $currency = Currency::load($element['#curr_id']);
    $element['chart'] = [
      '#theme' => 'ordered_wallets',
      '#id' => 'ordered_wallets_' . $currency->id() . '_' . $i++,
      // '#title' => $element['#title'],.
      '#values' => $element['#data'],
      '#width' => 200,
    // Any smaller and gChart axis labels don't show.
      '#height' => 125,
      '#class' => ['ordered-wallets'],
    ];
    if ($element['#format_vals']) {
      $tick = static::getTick($element['#data']);
      $element['chart']['#vticks'][$tick] = $currency->format($tick, Currency::DISPLAY_NORMAL, FALSE);
    }
    if (!empty($element['#top'])) {
      $element['top'] = [
        '#theme' => 'item_list',
        '#list_type' => 'ol',
        '#items' => [],
      ];
      // @todo put titles on list items "See record for %name"
      $data = array_reverse($element['#data'], TRUE);
      while (list($wid, $val) = each($data)) {
        $wallet = Wallet::load($wid);
        if ($element['#users_only'] && $wallet->holder_entity_type->value != 'user') {
          continue;
        }
        if ($element['#format_vals']) {
          $val = $currency->format($val, Currency::DISPLAY_NORMAL, FALSE);
        }
        $element['top']['#items'][] = ['#markup' => $wallet->getHolder()->toLink()->toString() . ' (' . $val . ')'];
        if (count($element['top']['#items']) >= $element['#top']) {
          break;
        }
      }
    }
    return $element;
  }

  /**
   * Get a nice round number to as the axis maximum.
   *
   * @param array $vals
   *   The values to be represented.
   *
   * @return int
   *   The raw value to put on the axis.
   */
  public static function getTick(array $vals) {
    $max = max($vals);
    $tick = str_pad(1, strlen($max), '0');
    $val = 0;
    while ($val < $max - $tick) {
      $val += $tick;
    }
    return $val;
  }

}
