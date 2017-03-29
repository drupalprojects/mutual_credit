<?php

/**
 * @file
 */
namespace Drupal\mcapi_forms\Plugin\migrate\process;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;

/**
 * Convert the form template
 *
 * @MigrateProcessPlugin(
 *   id = "d7_mcapi_form_template"
 * )
 */
class McapiFormTemplate extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   *
   * Old template is of the form:
   * Payer: [mcapiform:payer] Payee: [mcapiform:payee] [mcapiform:worth] for [mcapiform:description]
   *
   * @todo handle line breaks?
   *
   * @todo remove the submit button?
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    return str_replace(['[mcapiform:', ']'], ['{{ ', ' }}'], $value->template);
  }

}
