<?php

namespace Drupal\mcapi\Plugin\Block;

use Drupal\mcapi\Exchange;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;

/**
 * Displays all the wallets of an entity.
 *
 * Entity being either the current user OR the entity being viewed. Shows the
 * wallet view mode 'mini'.
 *
 * @Block(
 *   id = "currency_summary",
 *   admin_label = @Translation("Currency summary"),
 *   category = @Translation("Community Accounting")
 * )
 */
class Currencies extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * {@inheritdoc}
   */
  public function build() {
    $currencies = mcapi_currencies_available();
    return $this->entityTypeManager
      ->getViewBuilder('mcapi_currency')
      ->viewMultiple($currencies, 'summary');
  }

}
