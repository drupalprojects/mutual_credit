<?php

namespace Drupal\mcapi\Plugin\migrate\cckfield;

use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate_drupal\Plugin\migrate\cckfield\CckFieldPluginBase;

/**
 * @MigrateCckField(
 *   id = "worth",
 *   core = {7}
 * )
 */
class Worth extends CckFieldPluginBase {


  /**
   * {@inheritdoc}
   */
  public function processField(MigrationInterface $migration) {
    $process = [
      0 => [
        'map' => [
          $this->pluginId => [
            $this->pluginId => $this->pluginId
          ]
        ]
      ]
    ];
    print_r($process);die('WorthField::processField');
    $migration->mergeProcessOfProperty('type', $process);
  }

  /**
   * {@inheritdoc}
   */
  public function processFieldInstance(MigrationInterface $migration) {
    die('processFieldInstance::worth');
  }

  /**
   * {@inheritdoc}
   */
  public function processCckFieldValues(MigrationInterface $migration, $field_name, $data) {
    die('processCckFieldValues');
    $process = [
      'plugin' => 'iterator',
      'source' => $field_name,
      'process' => [
        'curr_id' => 'currcode',
        'value' => [
          'plugin' => 'worth',
          'source' => $field_name,
        ],
      ],
    ];
    $migration->mergeProcessOfProperty($field_name, $process);
  }


  /**
   * {@inheritdoc}
   */
  public function getFieldWidgetMap() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getFieldFormatterMap() {
    return [];
  }

}
