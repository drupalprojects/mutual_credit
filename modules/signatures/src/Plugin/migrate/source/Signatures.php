<?php

namespace Drupal\mcapi_signatures\Plugin\migrate\source;

use Drupal\migrate_drupal\Plugin\migrate\source\DrupalSqlBase;

/**
 * Drupal signatures source from database.
 *
 * @MigrateSource(
 *   id = "d7_mcapi_signatures"
 * )
 */
class Signatures extends DrupalSqlBase {

  /**
   * {@inheritdoc}
   */
  public function query() {
    $query = $this->select('mcapi_signatures', 's')->fields('s', ['serial', 'uid', 'pending']);
    $query->join('mcapi_transactions', 't', 't.serial = s.serial');
    $query->addfield('t', 'created');
    $query->addfield('t', 'creator');
    return $query;
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    return [
      'serial' => $this->t('The serial number'),
      'uid' => $this->t('ID of the signer'),
      'pending' => $this->t('TRUE if the signature is still needed'),
      'created' => $this->t('Unixtime the transaction was created')
    ];
  }


  /**
   * Defines the source fields uniquely identifying a source row.
   *
   * None of these fields should contain a NULL value. If necessary, use
   * prepareRow() or hook_migrate_prepare_row() to rewrite NULL values to
   * appropriate empty values (such as '' or 0).
   *
   * @return array
   *   Array keyed by source field name, with values being a schema array
   *   describing the field (such as ['type' => 'string]).
   */
  public function getIds() {
    return [
      'serial' => [
        'type' => 'string',
        'alias' => 's'
      ],
      'uid' => [
        'type' => 'integer',
        'alias' => 's'
      ]
    ];
  }

}
