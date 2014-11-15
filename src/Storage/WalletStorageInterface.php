<?php

/**
 * @file
 * Contains \Drupal\mcapi\Storage\WalletStorageInterface.
 */

namespace Drupal\mcapi\Storage;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\ContentEntityInterface;

interface WalletStorageInterface extends EntityStorageInterface {

  /**
   * get the wallets which belong to any entity
   *
   * @param ContentEntityInterface $entity
   *
   * @return array
   *   wallet ids
   *
   * @todo REPLACE this with $this->filter()
   */
  public static function getOwnedIds(ContentEntityInterface $entity);


  /**
   * get a selection of wallets, according to $conditions
   *
   * @param array $conditions
   *   options are:
   *   entity_types, an array of entitytypeIds
   *   array exchanges, an array of exchange->id()s
   *   string fragment, part of the name of the wallet or parent entity
   *   wids, wallet->id()s to restrict the results to
   *   owner, a ContentEntity of a type which according to wallet settings, could have children
   *
   * @param $boolean $offset
   *
   * @param $boolean $limit
   *
   * @param boolean $intertrading
   *   TRUE if the '_intertrading' wallets should be included.
   *
   * @return array
   *   The wallet ids
   */
  public static function filter(array $conditions, $offset = 0, $limit = NULL, $intertrading = FALSE);

}
