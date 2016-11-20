<?php

namespace Drupal\mcapi\Plugin\views\field;

use Drupal\views\ResultRow;
use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\mcapi\Mcapi;
use Drupal\mcapi\Entity\Wallet;

/**
 * Field handler provides current stat for given wallet via Wallet::getStatAll.
 *
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
      $entities = Mcapi::walletsOf($entity, TRUE);
      $wallet = reset($entities);
    }
    else {
      $wallet = $entity;
    }
    if ($wallet) {
      $stat = $this->definition['stat'];
      $val = $wallet->getStatAll($stat);
      switch ($stat) {
        case 'volume':
        case 'incoming':
        case 'outgoing':
        case 'balance':
          return [
            '#type' => 'worths_view',
            '#worths' => $val,
          ];

        case 'trades':
        case 'partners':
          return $val;
      }
    }
    return $this->t('No transactions yet.');
  }

}
