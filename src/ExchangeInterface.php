<?php

/**
 * @file
 * Contains \Drupal\comment\ExchangeInterface.
 */

namespace Drupal\mcapi;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface defining an exchange entity.
 */
interface ExchangeInterface extends ContentEntityInterface, EntityOwnerInterface {

  /**
   * get the number of users in this exchange
   * @return integer
   */
  function members();


  /**
   * get the number of transactions in this exchange's history
   * @return integer
   */
  function transactions($period);

  /**
   * Check if a (content) entity is a member of this exchange
   * the entity must have an entity reference instance set to $this EntityType
   * @param ContentEntityInterface $entity
   * @return Boolean
   *   TRUE if the entity is a member
   */
  public function member(ContentEntityInterface $entity);

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

  /**
   * get the wid of the this exchange'sintertrading wallet
   * @return integer
   */
  function intertrading_wallet()

}
