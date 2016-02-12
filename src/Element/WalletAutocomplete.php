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
    return $info;
  }

}
