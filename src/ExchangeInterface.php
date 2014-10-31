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
  function users();

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

  /**
   * get the number of transactions in this exchange, counted by serial number
   *
   * @return integer
   */
  function transactions();

  /**
   * return a list of exchanges from an entity_reference field in an entity
   * If an exchange is passed, it returns itself
   *
   * @param ContentEntityInterface $entity
   *   any Content Entity which has a reference field pointing towards mcapi_exchange entities
   *
   * @return array
   *   of entities, keyed by exchange id
   */
  static function referenced_exchanges(ContentEntityInterface $entity = NULL, $enabled = FALSE, $open = FALSE);

  /**
   * get a list of all the entity types which have an entity reference field pointing to mcapi_exchange
   *
   * @param string $type
   *   (optional) the name of the entity type
   *
   * @return array
   *   a mapping of entityTypeId to the name of the exchanges entity_reference
   *   field or the fieldname for the given entitytype
   */
  static function getEntityFieldnames();

  /**
   * find out whether an exchange can be deleted
   * i.e. that is has
   * * no intertrading transactions,
   * * no wallets, and
   * * is disabled
   *
   * @return Boolean
   */
  function deletable();
}
