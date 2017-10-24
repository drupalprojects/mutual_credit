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
 *   id = "d7_mcapi_form_content"
 * )
 */
class McapiFormContent extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    module_load_include('inc', 'mcapi_forms');
    $source = $row->getSource();
    $used_field_map = mcapi_forms_migrate_template_field_map(
      $source['experience']->template,
      $source['direction']->preset
    );

    if ($this->configuration['show']) {
      foreach ($used_field_map as $old_field_name => $new_field_name) {
        $content[$new_field_name] = $this->transactionFields($new_field_name);
        $content[$new_field_name]['weight'] = 1;
      }
    }
    else {
      $form_fields = $this->getFormFieldDefinitions();
      $hidden_fields = array_diff(array_keys($form_fields), $used_field_map);
      foreach ($hidden_fields as $field_name) {
        $content[$field_name] = 1;
      }
    }
    return $content;
  }


  function transactionFields($fieldname = NULL) {
    $fields['description'] = [
      'type' => 'string_textfield',
      'settings' => [
        'size' => 60,
        'placeholder' => 'What for?'
      ]
    ];
    $fields['created'] = [
      'type' => 'datetime_timestamp',
      'settings' => []
    ];
    $fields['worth'] = [
      'type' => 'worth',
      'settings' => [
        'exclude' => 0,
        'currencies' => []//not gonna bother
      ]
    ];
    $fields['payer'] = [
      'type' => 'my_wallet',
      'settings' => [
        'match_operator' => 'CONTAINS',
        'size' => 60,
        'placeholder' => 'Payer wallet',
        'hide_one_wallet' => 0
      ]
    ];

    $fields['payee'] = $fields['payer'];
    $fields['payee']['settings']['placeholder'] = 'Payee wallet';

    // Pretty difficult to find the field mappings for additional fields on the transaction.
    $fields['category'] = [
      'type' => 'options_select'
    ];
    return $fieldname ? $fields[$fieldname] : array_keys($fields);
  }

  /**
   *
   * @return array
   */
  function getFormFieldDefinitions() {
    module_load_include('inc', 'mcapi_forms');
    $definitions = \Drupal::entityManager()->getFieldDefinitions('mcapi_transaction', 'mcapi_transaction');
    // Ignore fields whose definition states they should not be displayed.
    foreach ($definitions as $field_name => $def) {
      if (!$def->getDisplayOptions('form')) {
       unset($definitions[$field_name]);
      }
    }
    return $definitions;
  }

}
