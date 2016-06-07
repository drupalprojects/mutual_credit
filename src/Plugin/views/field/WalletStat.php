<?php

/**
 * @file
 * Definition of Drupal\mcapi\Plugin\views\field\WalletStat.
 */

namespace Drupal\mcapi\Plugin\views\field;

use Drupal\views\ResultRow;
use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\mcapi\Mcapi;
use Drupal\mcapi\Entity\Wallet;

/**
 * Field handler to provide current stat for a given wallet via Wallet::getStatAll
 * @note reads from the transaction index table
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("wallet_stat")
 */
class WalletStat extends FieldPluginBase {

  /**
   * {@inheritdoc}
   */
  public function query() {
    $this->addAdditionalFields();
  }

  /**
   * {@inheritdoc}
   */
  public function render(ResultRow $values) {
    $entity = $this->getEntity($values);
    if ($entity->getEntityTypeId() != 'mcapi_wallet') {
      $entities = Mcapi::walletsOf($entity);
      $wallet = Wallet::load(reset($entities));
    }
    else $wallet = $entity;
    $stat = $this->definition['stat'];
    $val = $wallet->getStatAll($stat);
    switch ($stat) {
      case 'volume':
      case 'incoming':
      case 'outgoing':
      case 'balance':
        return [
          '#type' => 'worths_view',
          '#worths' => $val
        ];
      case 'trades':
      case 'partners':
        return $val;
    }
  }

}
