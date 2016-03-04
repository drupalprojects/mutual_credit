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
    return $info;
  }

  public static function processEntityAutocomplete(array &$element, FormStateInterface $form_state, array &$complete_form) {
    if (!empty($element['#value'])) {
      $element['#type'] = 'item';
      $element['#markup'] = \Drupal\mcapi\Entity\Wallet::load($element['#value'])->label();
    }
    else {
      parent::processEntityAutocomplete($element, $form_state, $complete_form);
    }
    return $element;
  }

}
