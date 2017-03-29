<?php

namespace Drupal\mcapi_limits\Plugin\migrate\destination;

use Drupal\mcapi\Entity\Currency;
use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate\Row;
use Drupal\migrate\Plugin\migrate\destination\DestinationBase;

/**
 * Writes the limits as thirdPartySettings on a currency
 *
 * @MigrateDestination(
 *   id = "mcapi_currency_limits",
 * )
 */
class CurrencyLimits extends DestinationBase {

  protected $supportsRollback = TRUE;

  /**
   * {@inheritdoc}
   */
  public function import(Row $row, array $old_destination_id_values = array()) {
    $curr_id = $row->getSourceProperty('info')->currcode;
    $limits = $row->getDestinationProperty('limits');
    Currency::load($curr_id)
      ->setThirdPartySetting('mcapi_limits', 'personal', $limits['personal'])
      ->setThirdPartySetting('mcapi_limits', 'skip', $limits['skip'])
      ->setThirdPartySetting('mcapi_limits', 'prevent', $limits['prevent'])
      ->setThirdPartySetting('mcapi_limits', 'warning_mail', $limits['warning_mail'])
      ->setThirdPartySetting('mcapi_limits', 'prevented_mail', $limits['prevented_mail'])
      ->setThirdPartySetting('mcapi_limits', 'plugin', $limits['plugin'])
      ->setThirdPartySetting('mcapi_limits', 'plugin_settings', $limits['plugin_settings'])
      ->save();
    return [$curr_id];
  }

  /**
   * {@inheritdoc}
   */
  public function rollback(array $destination_identifier) {
    Currency::load($destination_identifier['curr_id'])
      ->unsetThirdPartySetting('mcapi_limits', 'personal')
      ->unsetThirdPartySetting('mcapi_limits', 'skip')
      ->unsetThirdPartySetting('mcapi_limits', 'warning_mail')
      ->unsetThirdPartySetting('mcapi_limits', 'prevented_mail')
      ->unsetThirdPartySetting('mcapi_limits', 'plugin')
      ->unsetThirdPartySetting('mcapi_limits', 'plugin_settings')
      ->save();
  }


  /**
   * {@inheritdoc}
   */
  public function fields(MigrationInterface $migration = NULL) {
    // TODO: Implement fields() method.
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
  protected function __getKey($key) {
    return [
      'curr_id' => ['type' => 'string']
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    $ids['curr_id']['type'] = 'string';
    return $ids;
  }

}
