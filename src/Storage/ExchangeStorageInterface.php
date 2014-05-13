<?php

/**
 * @file
 * Contains \Drupal\mcapi\Storage\ExchangeStorageInterface.
 */

namespace Drupal\mcapi\Storage;

use Drupal\Core\Entity\FieldableEntityStorageInterface;
use Drupal\Core\Entity\EntityInterface;

interface ExchangeStorageInterface extends FieldableEntityStorageInterface {


  /**
   * check if an exchange, and all the transactions in it can be deleted, which means both:
   * the exchange is already disabled (closed)
   * the delete mode allows its transactions to be deleted.
   *
   * @param EntityInterface $exchange
   * @return Boolean
   */
  function deletable(EntityInterface $exchange);

  /**
   * check if an exchange can be deactivated, which means that it is not the only active exchange
   *
   * @param EntityInterface $exchange
   * @return Boolean
   */
  function deactivatable($exchange);

}
