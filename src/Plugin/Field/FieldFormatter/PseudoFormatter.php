<?php

/**
 * @file
 * Contains \Drupal\Core\Field\Plugin\Field\FieldFormatter\PseudoFormatter.
 */

namespace Drupal\mcapi\Plugin\Field\FieldFormatter;

use Drupal\Component\Utility\String;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Field\FieldItemListInterface;

/**
 * Plugin implementation of the 'worth' formatter.
 *
 * @FieldFormatter(
 *   id = "worth_pseudo",
 *   label = @Translation("Pseudo currency value"),
 *   field_types = {
 *     "worth"
 *   }
 * )
 */
class PseudoFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items) {
    $elements = array();
    
    foreach ($items as $delta => $item) {
      extract($this->getvalue());
      $elements[$delta] = array('#markup' => mcapi_currency_load($curr_id)->faux_format($value));
    }
    return $elements;
  }

}
