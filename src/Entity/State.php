<?php

/**
 * @file
 * Contains Drupal\mcapi\Entity\State.
 */

namespace Drupal\mcapi\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\user\RoleInterface;

/**
 * Defines the transaction state entity class.
 *
 * @ConfigEntityType(
 *   id = "mcapi_state",
 *   label = @Translation("State"),
 *   controllers = {
 *     "storage" = "Drupal\Core\Config\Entity\ConfigEntityStorage",
 *   },
 *   config_prefix = "state",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *   }
 * )
 */
class State extends ConfigEntityBase {

  /**
   * Identifier for the current State
   * Must be an integer
   * Positive values count towards the user balance
   * 0 is reserved for the Deleted state
   *
   * @var string
   */
  public $id;

  /**
   * Label of the current state
   *
   * @var string
   */
  public $label;

  /**
   * Description of the current State
   *
   * @var string
   */
  public $description;

}
