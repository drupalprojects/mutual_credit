<?php

namespace Drupal\mcapi_signatures\Plugin\migrate\destination;

use Drupal\migrate\Row;
use Drupal\migrate\Plugin\migrate\destination\DestinationBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\Core\Database\Driver\mysql\Connection;

/**
 * Destination for mcapi_signatures.
 *
 * @MigrateDestination(
 *   id = "mcapi_signatures"
 * )
 * @note Unusually we're writing directly to the database because there is no
 * way to add arbitrary signatures to transaction objects.
 */
class Signatures extends DestinationBase implements ContainerFactoryPluginInterface {

  protected $supportsRollback = TRUE;

  private $database;

  /**
   * Constructor
   *
   * @param array $configuration
   * @param string $plugin_id
   * @param array $plugin_definition
   * @param MigrationInterface $migration
   * @param Connection $database
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, MigrationInterface $migration, Connection $database) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $migration);
    $this->database = $database;
  }

  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition, MigrationInterface $migration = NULL) {
    return new static (
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
    try {
      // For some reason the upgrade routing is running everything twice, so
      // this insert is causing errors
      $this->database->insert('mcapi_signatures')
        ->fields($row->getDestination())
        ->execute();
    }
    catch (\Exception $e) {
      // Do nothing
    }
    return [$row->getDestinationProperty('serial'), $row->getDestinationProperty('uid')];
  }

  /**
   * {@inheritdoc}
   */
  public function fields(MigrationInterface $migration = NULL) {
    return ['serial', 'uid', 'signed'];
  }

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    return [
      'serial' => ['type' => 'string'],
      'uid' => ['type' => 'integer']
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function rollback(array $destination_identifier) {
    $this->database->delete('mcapi_signatures')
      ->condition('serial', $destination_identifier['serial'])
      ->condition('uid', $destination_identifier['uid'])
      ->execute();
  }

}
