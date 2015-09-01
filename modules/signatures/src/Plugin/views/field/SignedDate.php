<?php

/**
 * @file
 * Contains \Drupal\mcapi_signatures\Plugin\views\field\SignedDate.
 */

namespace Drupal\mcapi_signatures\Plugin\views\field;

use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\ResultRow;
use Drupal\mcapi\Entity\Transaction;

/**
 *
 * @ViewsField("signed_date")
 */
class SignedDate extends FieldPluginBase {

  /**
   * {@inheritdoc}
   */
  public function render(ResultRow $values) {
    $val = $this->getValue($values);
    if ($val) return $val;
    return t('Awaiting signature');
  }
}
