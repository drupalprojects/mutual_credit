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
use Drupal\Component\Utility\Tags;

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
    $info['#exclude'] = [];
    return $info;
  }

  /**
   * {@inheritdoc}
   *
   */
  public static function __extractEntityIdFromAutocompleteInput($input) {
    $match = NULL;
    // Take "label #entity id', match the ID from number after #
    if (preg_match("/.*\#(\d+)/", $input, $matches)) {
      $match = $matches[1];
    }
    return $match;
  }

  /**
   * {@inheritdoc}
   */
  public static function __getEntityLabels(array $entities) {
    $wallet_labels = [];
    foreach ($entities as $wallet) {
      // Labels containing commas or quotes must be wrapped in quotes.
      $wallet_labels[] = Tags::encode($wallet->label())  . ' #' . $wallet->wid->value;
    }
    return implode(', ', $wallet_labels);
  }



}
