<?php

/**
 * @file
 * Contains \Drupal\mcapi\Plugin\views\argument\EntityExchanges.
 */

namespace Drupal\mcapi\Plugin\views\argument;

use Drupal\Component\Utility\String;
use Drupal\Core\Entity\EntityStorageControllerInterface;
use Drupal\views\Plugin\views\argument\Numeric;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Argument handler to accept a user id.
 *
 * @ingroup views_argument_handlers
 *
 * @PluginID("entity_exchanges")
 */
class EntityExchanges extends Numeric {

  public function getTitle() {
     return entity_load('mcapi_exchange', $this->getValue())->label();
  }
}
