<?php

/**
 * @file
 * Contains \Drupal\mcapi\Element\WalletAutocomplete
 */

namespace Drupal\mcapi\Element;

use Drupal\Core\Entity\Element\EntityAutocomplete;
use Drupal\Component\Utility\Tags;

/**
 * Provides a widget to select wallet references by name, using autocomplete
 * This field can ONLY be used on fields called payer and payee;
 * it reads the fieldname to know what direction it is going in
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
  public static function getEntityLabels(array $entities) {die('getEntityLabels GOOD');
    $wallet_labels = [];
    foreach ($entities as $wallet) {
      $label = ($wallet->access('view')) ?
        $wallet->label() :
        $this->t('- Restricted access -');
      // Labels containing commas or quotes must be wrapped in quotes.
      $wallet_labels[] = Tags::encode($label)  . ' #' . $wallet->wid->value;
    }
    return implode(', ', $wallet_labels);
  }



}
