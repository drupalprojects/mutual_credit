<?php

/**
 * @file
 * Contains \Drupal\mcapi\ExchangeInterface.
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
   * @param integer $period
   *   unixtime to count transactions from
   * @return integer
   */
  function transactions($period = 0);

  /**
   * Check if a (content) entity is a member of this exchange
   * the entity must have an entity reference instance set to $this EntityType
   * @param ContentEntityInterface $entity
   * @return Boolean
   *   TRUE if the entity is a member
   */
  public function member(ContentEntityInterface $entity);

  /**
   * get the wid of the this exchange's intertrading wallet
   * @return integer
   */
  function intertrading_wallet();

}
