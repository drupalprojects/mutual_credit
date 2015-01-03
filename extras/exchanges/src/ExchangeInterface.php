<?php

/**
 * @file
 * Contains \Drupal\mcapi_exchanges\ExchangeInterface.
 */

namespace Drupal\mcapi_exchanges;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface defining an exchange entity.
 */
interface ExchangeInterface extends ContentEntityInterface, EntityOwnerInterface {

  /**
   * get the wid of the this exchange's intertrading wallet
   * @return integer
   */
  function intertrading_wallet();

  /**
   * get the number of transactions in this exchange, counted by serial number
   *
   * @return integer
   */
  function transactions();

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
  
  
  /**
   * check if an exchange can be deactivated, which means that it is not the only active exchange
   * 
   * @return Boolean
   */
  function deactivatable();
  
  /**
   * return the user ids of all the members in this exchange
   * 
   * @return integer[]
   *   the user ids
   */
  public function users();
}
