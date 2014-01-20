<?php

/**
 * @file
 * Contains Drupal\mcapi\Entity\Type.
 */

namespace Drupal\mcapi\Entity;

use Drupal\Core\Entity\Entity;

/**
 * This is the smallest possible entity,
 * serving only to act as a Parent for system wallets, instantiated on the fly
 *
 * @EntityType(
 *   id = "bank",
 *   label = @Translation("Bank"),
 *   controllers = {
 *     "storage" = "Drupal\mcapi\BankStorageController"
 *   },
 *   entity_keys = {
 *     "id" = "id",
 *   }
 * )
 */
class Bank extends Entity {
  //an array of references to wallet arrays
  var $wallets;

  public function __construct() {
    $this->entityType = 'bank';
    //not sure how this is populated yet, or how necessary it is
    $this ->wallets = array();
  }
  public function label($langcode = NULL) {
    return t('Bank', array(), array('langcode' => $langcode));
  }
}

