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
    $source = $row->getSource();
    $show = $this->configuration['show'];
    $fieldnames = $this->neededHere($source['experience']->template, $show);

    foreach ($fieldnames as $fieldname) {
      $fields[$fieldname] = $show ? $this->apiFields($fieldname) : 1;
    }

    // Alter the fields according to context
    if ($show) {
      if ($source['perspective'] == 1) {
        // The wallets give were set up for incoming
        if ($source['direction']->preset == 'outgoing') {
          $fields['payer'] = [
            'type' => 'my_wallet',
            'settings' => ['hide_one_wallet' => 0]
          ];
        }
        else {
          $fields['payee'] = [
            'type' => 'my_wallet',
            'settings' => ['hide_one_wallet' => 0]
          ];
        }
      }
    }
    return $fields;
  }

  /**
   *
   * @param string $template
   * @param bool $show
   *
   * @return $string[]
   *   The fieldnames
   */
  function neededHere($template, $show) {
    foreach ($this->apiFields() as $fieldname) {
      $pos = strpos($template, $fieldname.']');
      if ($show == (bool)$pos) {
        $needed[] = $fieldname;
      }
    }
    return $needed;
  }

  function apiFields($fieldname = NULL) {
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
    $fields['payee'] = [
      'type' => 'wallet_reference_autocomplete',
      'settings' => [
        'match_operator' => 'CONTAINS',
        'size' => 60,
        'placeholder' => 'Payee wallet',
        'hide_one_wallet' => 0
      ]
    ];
    $fields['payer'] = [
      'type' => 'wallet_reference_autocomplete',
      'settings' => [
        'match_operator' => 'CONTAINS',
        'size' => 60,
        'placeholder' => 'Payer wallet',
        'hide_one_wallet' => 0
      ]
    ];
    return $fieldname ? $fields[$fieldname] : array_keys($fields);
  }

}
