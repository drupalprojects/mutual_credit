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
    $template = str_replace('[mcapiform:direction]', '', $value->template);
    module_load_include('inc', 'mcapi_forms');
    // Replace the tokens
    $map = mcapi_forms_migrate_template_field_map($template, $row->getSourceProperty('direction')->preset);
    $form = $to = [];
    foreach ($map as $old => $new) {
      $from[] = '[mcapiform:'.$old.']';
      $to[] = "{{ $new }}";
    }
    $template = str_replace($from, $to, $template);
    return $template;
  }

}
