<?php

namespace Drupal\mcapi\Plugin\views\field;

use Drupal\views\Plugin\views\field\Standard;
use Drupal\views\ResultRow;
use Drupal\Core\Form\FormStateInterface;

/**
 * Field handler to link the transaction description to the transaction itself.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("mcapi_wallet_holder")
 */
class WalletHolder extends Standard {

  /**
   * {@inheritdoc}
   */
  public function query() {
    // $this->addAdditionalFields();
  }

  /**
   * {@inheritdoc}
   */
  public function render(ResultRow $values) {
    $wallet = $this->getEntity($values);
    return $wallet->getHolder()->toLink()->toString();
  }

}
