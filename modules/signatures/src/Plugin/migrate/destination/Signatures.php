<?php

namespace Drupal\mcapi_signatures\Plugin\migrate\destination;

use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate\Row;
use Drupal\migrate\Plugin\migrate\destination\DestinationBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\migrate\Entity\MigrationInterface;

/**
 * Destination for mcapi_signatures.
 *
 * @MigrateDestination(
 *   id = "d7_mcapi_signatures"
 * )
 */
class Signatures extends DestinationBase {

  private $database;

  /**
   *
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, MigrationInterface $migration, $database) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $migration);
    $this->database = $database;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition, MigrationInterface $migration = NULL) {
    $entity_type_id = static::getEntityTypeId($plugin_id);
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $migration,
      $container->get('database')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function import(Row $row, array $old_destination_id_values = array()) {
    $this->rollbackAction = MigrateIdMapInterface::ROLLBACK_DELETE;

    // These is probably a better way than this.
    $this->database->merge('mcapi_signatures')
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
  public function fields(MigrationInterface $migration = NULL) {
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
