<?php

/**
 * @file
 * Contains \Drupal\mcapi_exchanges\Plugin\views\argument\EntityExchanges.
 */

namespace Drupal\mcapi\Plugin\views\argument;

use Drupal\views\Plugin\views\argument\Numeric;
use Drupal\mcapi\Entity\Exchange;

/**
 * Argument handler to accept a user id.
 *
 * @ingroup views_argument_handlers
 *
 * @ViewsArgument("entity_exchanges")
 */
class EntityExchanges extends Numeric {

  public function getTitle() {
    return Exchange::load($this->getValue())->label();
  }
}
