<?php

namespace Drupal\mcapi\Plugin\migrate\source\d7;

use Drupal\migrate_drupal\Plugin\migrate\source\DrupalSqlBase;
use Drupal\migrate\Row;

/**
 * Currencies in d7 were built on ctools
 *
 * @MigrateSource(
 *   id = "d7_mcapi_ctools"
 * )
 */
class Ctools extends DrupalSqlBase {

  /**
   * {@inheritdoc}
   */
  public function query() {
    $table = $this->configuration['table'];
    $key = $this->configuration['key'];
    // NB Only overriden ctools objects are even stored in the database.
    return $this->select($table, 't')
      ->fields('t', [$key]);
  }

  protected function values() {
    return $this->prepareQuery()->execute()->fetchCol();
  }

  /**
   * {@inheritdoc}
   */
  public function count() {
    return intval($this->query()->countQuery()->execute()->fetchField() > 0);
  }


  /**
   * {@inheritdoc}
   */
  public function prepareRow(Row $row) {
    static $i = 0;

    $table = $this->configuration['table'];
    $key = $this->configuration['key'];

    $id = $row->getSourceProperty($key);
    $row->setSourceProperty('tempid', $i++);

    $result = $this->select($table, 'c')
      ->fields('c', ['data'])
      ->condition($key, $id)
      ->execute()
      ->fetchField();

    $currency = unserialize($result);
    foreach ((array)$currency as $key => $val) {
      if (is_array($val)) {
        $val = (object)$val;
      }
      $row->setSourceProperty($key, $val);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    $fields = [
      $this->configuration['key'] => $this->t('Ctools key'),
      'data' => $this->t('Serialized data'),
    ];
    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    return [
      'tempid' => [
        'type' => 'integer',
      ]
    ];
  }

}
