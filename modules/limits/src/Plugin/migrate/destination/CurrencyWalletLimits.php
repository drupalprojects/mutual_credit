<?php

namespace Drupal\mcapi_limits\Plugin\migrate\destination;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate\Row;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\migrate\Plugin\migrate\destination\DestinationBase;
use Drupal\Core\Database\Connection;

/**
 * Writes the limits as thirdPartySettings on a currency
 *
 * @MigrateDestination(
 *   id = "mcapi_currency_wallet_limits",
 * )
 */
class CurrencyWalletLimits extends DestinationBase implements ContainerFactoryPluginInterface{

  protected $supportsRollback = TRUE;

  private $database;

  /**
   * Constructor
   * See parents, plus
   * @param Connection $database
   */
  public function __construct($configuration, $plugin_id, $plugin_definition, MigrationInterface $migration, Connection $database) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $migration);
    $this->database = $database;
  }

  /**
   * I think this is a bug in 8.3.0-beta1 because the interface prevents the last arg from being passed.
   * @see \Drupal\migrate\Plugin\MigratePluginManager::createInstance
   */
  public static function create(ContainerInterface $container, array $config, $plugin_id, $plugin_definition) {
    $args = func_get_args();
    return new static (
      $config,
      $plugin_id,
      $plugin_definition,
      array_pop($args),
      $container->get('database')
    );
  }

  /**
   * {@inheritdoc}
   * Incoming from the process plugin, a row with one field, $limit, which may
   * have many rows thus
   *  [
        'wid' => $wid,
        'curr_id' => $curr_id,
        'max' => $limits['max'],
        'min' => $limits['min'],
        'editor' => 1, //User 1 because this field didn't exist in 7
        'date' => REQUEST_TIME
      ];
   */
  public function import(Row $row, array $old_destination_id_values = array()) {
    if ($limits = $row->getDestinationProperty('limits')) {
      $query = $this->database->insert('mcapi_wallets_limits')
        ->fields(['wid', 'curr_id', 'max', 'value', 'editor', 'date']);
      foreach ($limits as $limit) {
        $query->values($limit);
      }
      $query->execute();
      return [reset($limits)['wid']];
    }
  }

  /**
   * {@inheritdoc}
   */
  public function fields(MigrationInterface $migration = NULL) {
    // TODO: Implement fields() method.
  }

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    $ids['wid']['type'] = 'integer';
    return $ids;
  }
  /**
   * Returns a specific entity key.
   *
   * @param string $key
   *   The name of the entity key to return.
   *
   * @return string|bool
   *   The entity key, or FALSE if it does not exist.
   *
   * @see \Drupal\Core\Entity\EntityTypeInterface::getKeys()
   */
  protected function getKey($key) {
    return [
      'wid' => ['type' => 'integer']
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function rollback(array $destination_identifier) {
    $this->database
      ->delete('mcapi_wallets_limits')->condition('wid', $destination_identifier['wid'])
      ->execute();

    echo 'ROLLEDBACK';
  }


}
