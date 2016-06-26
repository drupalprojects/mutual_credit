<?php

namespace Drupal\mcapi\Entity;
use \Drupal\Core\Config\Entity\ConfigEntityBase;

/**
 * Defines the transaction state entity class.
 *
 * @ConfigEntityType(
 *   id = "mcapi_state",
 *   label = @Translation("Transaction state"),
 *   config_prefix = "state",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *   }
 * )
 */
class State extends ConfigEntityBase {

  /**
   * Identifier for the current State.
   *
   * @var string
   */
  public $id;

  /**
   * Label of the current state.
   *
   * @var string
   */
  public $label;

  /**
   * Description of the current State.
   *
   * @var string
   */
  public $description;

  /**
   * The module which provides this plugin.
   *
   * @var string
   */
  public $provider;

  /**
   * Whether or not transactions in this state counts towards the stats.
   *
   * @var boolean
   *
   * @note This can overridden by user 1 on the misc settings page
   */
  public $counted;

  /**
   * Magic.
   */
  public function __toString() {
    return $this->id;
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() {
    parent::calculateDependencies();
    $this->addDependency('module', $this->provider);
    return $this->dependencies;
  }

}
