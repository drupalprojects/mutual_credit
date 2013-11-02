<?php


/**
 * @file
 * Contains \Drupal\mcapi\Plugin\formatter\Worth.
 */

namespace Drupal\mcapi\Plugin\field\formatter;

use Drupal\field\Plugin\Type\Formatter\FormatterBase;
use Drupal\Core\Entity\Field\FieldItemListInterface;

/**
 * Plugin implementation of the 'text_default' formatter.
 *
 * @FieldFormatter(
 *   id = "worths",
 *   label = @Translation("Worths"),
 *   field_types = {
 *     "worth",
 *   }
 * )
 */
class WorthsFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items) {
    $elements = array();

    foreach($items as $delta => $item) {
      $element[$delta] = array(
        '#theme' => 'worth_item',
        '#currcode' => $item['currcode'],
        '#quantity' => $item['quantity'],
      );
    }

    return $elements;
  }

}
