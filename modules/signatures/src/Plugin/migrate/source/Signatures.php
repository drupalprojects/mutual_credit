<?php

/**
 * @file
 * Contains \Drupal\mcapi_signatures\Plugin\migrate\source\Signatures.
 */

namespace Drupal\mcapi_signatures\Plugin\migrate\source;

/**
 * Drupal signatures source from database.
 *
 * @MigrateSource(
 *   id = "signatures"
 * )
 */
class Signatures extends DrupalSqlBase {

  public function query() {
    $query = $this->select('mcapi_signatures', 's')->fields('s', ['serial', 'uid', 'pending']);
    $query->join('mcapi_transaction', 't', 't.serial = s.serial');
    $query->addfield('t', 'created');
    return $query;
  }

}
