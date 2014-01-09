<?php

/**
 * @file
 * Contains Drupal\mcapi\Entity\Type.
 */

namespace Drupal\mcapi\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\Core\Entity\EntityStorageControllerInterface;
use Drupal\user\RoleInterface;

/**
 * Defines the transaction state entity class.
 *
 * @EntityType(
 *   id = "mcapi_type",
 *   label = @Translation("Type"),
 *   controllers = {
 *     "storage" = "Drupal\Core\Config\Entity\ConfigStorageController",
 *   },
 *   config_prefix = "mcapi.type",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *   }
 * )
 */
class Type extends ConfigEntityBase {

  /**
   * Identifier for the current Type
   *
   * @var string
   */
  public $id;

  /**
   * Label of the current Type
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

  /**
   * start of which to start with.
   *
   * @var integer
   */
  public $start_state;
}
