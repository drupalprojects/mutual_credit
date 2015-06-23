<?php

/**
 * @file
 * Contains \Drupal\mcapi\Controller\CurrencyController.
 *
 */

namespace Drupal\mcapi\Controller;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\Controller\EntityViewController;

/**
 * Returns responses for Wallet routes.
 */
class CurrencyController extends EntityViewController {

  public function title(EntityInterface $mcapi_currency) {
    return $mcapi_currency->label();
  }

}
