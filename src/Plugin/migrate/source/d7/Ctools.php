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
    // NB Only overriden ctools objects are even stored in the database.
    return $this->select($this->configuration['table'], 't')
      ->fields('t', [$this->configuration['column']]);
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
    $result = $this->select($this->configuration['table'], 'c')
      ->fields('c', ['data'])
      ->condition($this->configuration['column'], $row->getSourceProperty($this->configuration['column']))
      ->execute()
      ->fetchField();

    $currency = unserialize($result);
    foreach ((array)$currency as $key => $val) {
      if (is_array($val)) {
        $val = (object)$val;
      }
      $row->setSourceProperty($key, $val);
    }
    return parent::prepareRow($row);
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    $fields = [
      $this->configuration['column'] => $this->t('Ctools key'),
      'data' => $this->t('Serialized data'),
    ];
    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    return [
      $this->configuration['column'] => [
        'type' => 'string',
      ]
    ];
  }

}
