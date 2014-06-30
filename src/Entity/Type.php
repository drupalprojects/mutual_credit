<?php

/**
 * @file
 * Contains Drupal\mcapi\Entity\Type.
 */

namespace Drupal\mcapi\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\user\RoleInterface;

/**
 * Defines the transaction state entity class.
 *
 * @ConfigEntityType(
 *   id = "mcapi_type",
 *   label = @Translation("Transaction type"),
 *   controllers = {
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

  /**
   * The module which provides this plugin
   *
   * @var string
   */
  //public $module;


  function calculateDependencies() {
    $this->dependencies = array();
    $conditions = array('type' => $this->id);
    if (\Drupal::EntityManager()->getStorage('mcapi_transaction')->filter($conditions, 0, 1)) {
      $this->dependencies = array('module' => array($this->module));
    }
    //todo - the same for mcapi_type!
    return $this->dependencies;
  }

  public function getStartState() {
    $state = entity_load('mcapi_state', $this->start_state);
    return $state->value;
  }

}
