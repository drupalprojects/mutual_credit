<?php

/**
 * @file
 * Contains \Drupal\mcapi\CurrencyInterface.
 */

namespace Drupal\mcapi;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Provides an interface defining a node type entity.
 */
interface CurrencyInterface extends ConfigEntityInterface {

  public function format($value);

  public function transactions();

}