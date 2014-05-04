<?php


/**
 * @file
 * Contains \Drupal\mcapi\Plugin\Field\FieldFormatter\Worth.
 */

namespace Drupal\mcapi\Plugin\Field\FieldFormatter;

use Drupal\field\Plugin\Type\Formatter\FormatterBase;
use Drupal\Core\Entity\Field\FieldItemListInterface;

/**
 * Plugin implementation of the 'text_default' formatter.
 *
 * @FieldFormatter(
 *   id = "worth",
 *   label = @Translation("Worth"),
 *   field_types = {
 *     "worth",
 *   }
 * )
 */
class WorthFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   * TODO this is not called from anywhere in this module
   * note that toString is in Worth.php are we getting mixed up?
   */
  public function viewElements(FieldItemListInterface $items) {
    $elements = array();
die('WorthFormatter::viewElements');
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
