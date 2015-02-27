<?php

/**
 * @file
 * Contains \Drupal\Core\Field\Plugin\Field\FieldFormatter\RawFormatter.
 */

namespace Drupal\mcapi\Plugin\Field\FieldFormatter;

use Drupal\Component\Utility\String;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Field\FieldItemListInterface;

/**
 * Plugin implementation of the 'worth' formatter.
 *
 * @FieldFormatter(
 *   id = "worth_raw",
 *   label = @Translation("Raw stored value"),
 *   field_types = {
 *     "worth"
 *   }
 * )
 */
class RawFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items) {
    $elements = [];

    foreach ($items as $delta => $item) {
      extract($this->getvalue());
      $elements[$delta] = array('#markup' => $value);
    }
    return $elements;
  }

}
