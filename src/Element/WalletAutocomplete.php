<?php

namespace Drupal\mcapi\Element;

use Drupal\Core\Entity\Element\EntityAutocomplete;

/**
 * Provides a widget to select wallet references by name, using autocomplete.
 *
 * Its really just EntityAutocomplete with a lot of defaults.
 *
 * @FormElement("wallet_entity_auto")
 */
class WalletAutocomplete extends EntityAutocomplete {

  static $multiple;

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    $info = parent::getInfo();
    $info['#selection_handler'] = 'default:mcapi_wallet';
    $info['#target_type'] = 'mcapi_wallet';
    $info['#restrict'] = '';
    $info['#exclude'] = [];
    $info['#hidden'] = FALSE;
    $info['#maxlength'] = 64;
    // Appearance should be managed with css.
    $info['#size'] = 60;
    return $info;
  }

}
