<?php

namespace Drupal\mcapi_signatures\Plugin\migrate\destination;

use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate\Row;
use Drupal\migrate\Plugin\migrate\destination\DestinationBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Destination for mcapi_signatures.
 *
 * @MigrateDestination(
 *   id = "d7_mcapi_signatures"
 * )
 */
class Signatures extends DestinationBase {

  /**
   *
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, MigrationInterface $migration) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $migration);
  }

  /**
   * {@inheritdoc}
   */
  public function import(Row $row, array $old_destination_id_values = array()) {
    $this->rollbackAction = MigrateIdMapInterface::ROLLBACK_DELETE;

    // Can't inject this easily, but there may be a better way than writing to the db here
    \Drupal::database()->merge('mcapi_signatures')
      ->key([
        'serial' => $row->getSourceProperty('serial'),
        'uid' => $row->getSourceProperty('uid'),
      ])
      ->values([
        'created' => $row->getSourceProperty('created'),
      ])->execute();

    return TRUE;
  }

  /**
   *
   */
  public function fields(\Drupal\migrate\Plugin\MigrationInterface $migration = NULL) {
    return ['serial', 'uid', 'signed'];
  }

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    $ids['serial']['type'] = 'string';
    $ids['uid']['type'] = 'string';
    return $ids;
  }

}
