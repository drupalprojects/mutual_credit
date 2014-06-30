<?php

/**
 * @file
 * Contains \Drupal\Core\Field\Plugin\Field\FieldFormatter\WorthFormatter.
 */

namespace Drupal\mcapi\Plugin\Field\FieldFormatter;

use Drupal\Component\Utility\String;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Field\FieldItemListInterface;

/**
 * Plugin implementation of the 'worth' formatter.
 *
 * @FieldFormatter(
 *   id = "worth",
 *   label = @Translation("Currency value"),
 *   field_types = {
 *     "worth",
 *   }
 * )
 */
class WorthFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items) {
    $elements = array();

    foreach ($items as $delta => $item) {
      $elements[$delta] = array('#markup' => $item->view());
    }
    return $elements;
  }

}
