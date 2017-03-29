<?php

namespace Drupal\mcapi\Plugin\migrate\process;

use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;

/**
 * Transform the sentence from the d7 adhoc tokens to d8 twig
 *
 * @MigrateProcessPlugin(
 *   id = "mcapi_sentence"
 * )
 */
class Sentence extends ProcessPluginBase {

  /**
   * From something like
   * [transaction:payer] paid [transaction:payee] [transaction:worth]. [transaction:links]
   * to
   * [xaction:payer] paid [xaction:payee] [xaction:worth] for [xaction:description].
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    $value = str_replace('transaction_description', 'description', $value);
    $value = str_replace('transaction:', 'xaction:', $value);
    return $value;
  }

}
