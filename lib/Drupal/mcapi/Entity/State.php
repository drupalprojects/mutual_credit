<?php

/**
 * @file
 * Contains Drupal\mcapi\Entity\State.
 */

namespace Drupal\mcapi\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\Core\Entity\EntityStorageControllerInterface;
use Drupal\user\RoleInterface;

/**
 * Defines the transaction state entity class.
 *
 * @EntityType(
 *   id = "mcapi_state",
 *   label = @Translation("State"),
 *   controllers = {
 *     "storage" = "Drupal\Core\Config\Entity\ConfigStorageController",
 *   },
 *   config_prefix = "mcapi.state",
 *   entity_keys = {
 *     "id" = "id",
 *   }
 * )
 */
class State extends ConfigEntityBase {

  public $description;
  /**
   * The constant value which is stored in the database.
   * Positive values count towards the user balance
   * 0 is reserved for the Deleted state
   *
   * @var string
   */
  public $value;


}
