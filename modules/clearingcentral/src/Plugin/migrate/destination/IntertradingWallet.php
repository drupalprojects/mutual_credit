<?php

namespace Drupal\mcapi_cc\Plugin\migrate\destination;

use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate\Row;
use Drupal\migrate\Plugin\migrate\destination\DestinationBase;

/**
 * Writes the settings for the default intertrading wallet
 *
 * @MigrateDestination(
 *   id = "mcapi_wallet_intertrading"
 * )
 *
 * @todo This isn't tested.
 */
class IntertradingWallet extends DestinationBase {

  protected $supportsRollback = TRUE;

  /**
   * {@inheritdoc}
   */
  public function import(Row $row, array $old_destination_id_values = array()) {
    //Get the first and probably only intertrading wallet
    $wid = intertrading_wallet_id();
    mcapi_cc_save_wallet_settings(
      $wid,
      $curr_id,
      $row->getDestinationProperty('mcapi_cc_user'),
      $row->getDestinationProperty('mcapi_cc_pass')
    );
    return [$wid];
  }


  /**
   * {@inheritdoc}
   */
  public function rollback(array $destination_identifier) {
    \Drupal::keyValue('clearingcentral')->deleteAll();
  }

  /**
   * {@inheritdoc}
   */
  public function fields(MigrationInterface $migration = NULL) {
    // I'm not sure what to put here. We are really creating a new EntityFormDisplay, so probably need the fields for that.
    // However this doesn't seem to be used...
    die('Drupal\mcapi_forms\Plugin\migrate\destination\McapiForm::fields() not populated');
  }

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    $ids['wid']['type'] = 'string';
    return $ids;
  }


}