<?php

/**
 * @file
 * Contains \Drupal\mcapi\Entity\ExchangeInterface.
 */

namespace Drupal\mcapi\Entity;

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
   * Check if a (content) entity is a member of this exchange
   * the entity must have an entity reference instance set to $this EntityType
   * If a wallet is passed it will check the wallet parent
   * @param ContentEntityInterface $entity
   * @return Boolean
   *   TRUE if the entity is a member
   */
  public function is_member(ContentEntityInterface $entity);

  /**
   * get the wid of the this exchange's intertrading wallet
   * @return integer
   */
  function intertrading_wallet();

  /**
   * act on a new entity joining the exchange
   */
  function hello(ContentEntityInterface $entity);

  /**
   * act on an entity leaving the exchange
   */
  function goodbye(ContentEntityInterface $entity);
}
