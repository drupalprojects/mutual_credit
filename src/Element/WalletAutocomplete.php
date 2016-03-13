<?php

/**
 * @file
 * Contains \Drupal\mcapi\Element\WalletAutocomplete
 * @todo would be nice to use the #NUM as the wallet identifier,
 * but this would mean overwriting another class
 * @todo this doesn't support the exclude setting yet
 */

namespace Drupal\mcapi\Element;

use Drupal\Core\Entity\Element\EntityAutocomplete;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a widget to select wallet references by name, using autocomplete
 *
 * @FormElement("wallet_entity_auto")
 *
 */
class WalletAutocomplete extends EntityAutocomplete {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    $info = parent::getInfo();
    $info['#target_type'] = 'mcapi_wallet';
    $info['#restrict'] = '';
    $info['#exclude'] = [];
    $info['#hidden'] = FALSE;
    //$info['#pre_render'][] = [get_class($this), 'preRender'];
    return $info;
  }

  public static function preRender($element) {
    if (!empty($element['#value'])) {
      drupal_set_message($element['#value']);
      return $element;

      $element['#type'] = 'item';
      $element['#markup'] = \Drupal\mcapi\Entity\Wallet::load($element['#value'])->label();
    }
    return $element;
  }

}
