<?php

namespace Drupal\mcapi\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;

/**
 * Defines the transaction state entity class.
 *
 * @ConfigEntityType(
 *   id = "mcapi_type",
 *   label = @Translation("Transaction type"),
 *   config_prefix = "type",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *   }
 * )
 */
class Type extends ConfigEntityBase {

  /**
   * Identifier for the current Type.
   *
   * @var string
   */
  public $id;

  /**
   * Label of the current Type.
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
   * First state of a new transaction.
   *
   * @var integer
   */
  public $start_state;

  /**
   * The module providing this Type.
   *
   * @var string
   */
  public $provider;

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() {
    parent::calculateDependencies();
    $this->addDependency('module', $this->provider);
    return $this->dependencies;
  }

}
