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
 *   label = @Translation("Transaction state"),
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

  /**
   * The module which provides this plugin
   *
   * @var string
   */
  public $module;

  /**
   * Whether or not transactions in this state counts towards the transaction stats
   * N.B. This can overridden by user 1 on the misc settings page
   *
   * @var boolean
   */
  public $counted;

  function __toString() {
    return $this->id;
  }
}
