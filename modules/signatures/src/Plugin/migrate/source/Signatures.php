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
 *   id = "d7_mcapi_signatures"
 * )
 */
class Signatures extends \Drupal\migrate_drupal\Plugin\migrate\source\DrupalSqlBase {

  public function query() {
    $query = $this->select('mcapi_signatures', 's')->fields('s', ['serial', 'uid', 'pending']);
    $query->join('mcapi_transaction', 't', 't.serial = s.serial');
    $query->addfield('t', 'created');
    return $query;
  }

}