<?php

namespace Drupal\mcapi\Plugin\migrate;

use Drupal\migrate_drupal\Plugin\migrate\FieldMigration;

/**
 * Plugin class for Drupal 7 transaction migrations dealing with fields
 *
 * @note based on Drupal\user\Plugin\migrate\User which has one bundle only.
 *
 * @todo thie user migration is sure to change after 8.4-rc2
 */
class Transaction extends FieldMigration {


  /**
   * {@inheritdoc}
   */
  public function getProcess() {
    if (!$this->init) {
      $this->init = TRUE;
      $definition = [
        'source' => [
          'entity_type' => 'transaction',
          'ignore_map' => TRUE,
          'plugin' => 'd7_field_instance'
        ] + $this->source,
        'destination' => [
          'plugin' => 'null'
        ]
      ];
      $sourcePlugin = $this
        ->migrationPluginManager
        ->createStubMigration($definition)
        ->getSourcePlugin();
      // This gets all the d8 field instances
      foreach ($sourcePlugin as $row) {
        $field_name = $row->getSourceProperty('field_name');
        $field_type = $row->getSourceProperty('type');
        if (empty($field_type)) {
          continue;
        }
        if ($field_type == 'worth_field') {
          $this->fieldPluginManager->createInstance('worth', [], $this)->processFieldValues($this, 'worth', $row->getSource());
        }
        elseif ($this->fieldPluginManager->hasDefinition($field_type)) {
          if (!isset($this->fieldPluginCache[$field_type])) {
            $this->fieldPluginCache[$field_type] = $this->fieldPluginManager->createInstance($field_type, [], $this);
          }
          $info = $row->getSource();
          $this->fieldPluginCache[$field_type]
            ->processFieldValues($this, $field_name, $info);
        }
        else {
          if ($this->cckPluginManager->hasDefinition($field_type)) {
            if (!isset($this->cckPluginCache[$field_type])) {
              $this->cckPluginCache[$field_type] = $this->cckPluginManager->createInstance($field_type, [], $this);
            }
            $info = $row->getSource();
            $this->cckPluginCache[$field_type]
              ->processCckFieldValues($this, $field_name, $info);
          }
          else {
            $this->process[$field_name] = $field_name;
          }
        }
      }
    }
    return parent::getProcess();
  }

}
