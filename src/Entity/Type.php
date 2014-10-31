<?php

/**
 * @file
 * Contains Drupal\mcapi\Entity\Type.
 */

namespace Drupal\mcapi\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\user\RoleInterface;
use Drupal\mcapi\Entity\State;
/**
 * Defines the transaction state entity class.
 *
 * @ConfigEntityType(
 *   id = "mcapi_type",
 *   label = @Translation("Transaction type"),
 *   handlers = {
 *     "storage" = "Drupal\Core\Config\Entity\ConfigEntityStorage",
 *   },
 *   config_prefix = "type",
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
