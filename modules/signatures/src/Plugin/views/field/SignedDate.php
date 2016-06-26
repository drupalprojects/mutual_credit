<?php

namespace Drupal\mcapi_signatures\Plugin\views\field;

use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\ResultRow;

/**
 * Show the date that that a signature was added.
 *
 * @ViewsField("signed_date")
 */
class SignedDate extends FieldPluginBase {

  /**
   * {@inheritdoc}
   */
  public function render(ResultRow $values) {
    $val = $this->getValue($values);
    if ($val) {
      return $val;
    }
    return t('Awaiting signature');
  }

}
