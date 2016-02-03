<?php

/**
 * @file
 * Contains \Drupal\mcapi\CurrencyComputed.
 */

namespace Drupal\mcapi;

use Drupal\Core\TypedData\DataDefinitionInterface;
use Drupal\Core\TypedData\TypedDataInterface;
use Drupal\Core\TypedData\TypedData;

/**
 * A computed property for the loaded currency in a worth field.
 *
 * Required settings (below the definition's 'settings' key) are:
 *  - currency source: The currency
 */
class CurrencyComputed extends TypedData {

  /**
   * Cached computed currency.
   *
   * @var \Currency|null
   */
  protected $currency = NULL;

  /**
   * {@inheritdoc}
   */
  public function __construct(DataDefinitionInterface $definition, $name = NULL, TypedDataInterface $parent = NULL) {
    parent::__construct($definition, $name, $parent);
    if (!$definition->getSetting('currency source')) {
      throw new \InvalidArgumentException("The definition's 'currency source' key has to specify the a curr_id");
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getValue($langcode = NULL) {
    if ($this->currency !== NULL) {
      return $this->currency;
    }
    $curr_id = $this->definition->getSetting('currency source');
    $value = $this->getParent()->{$curr_id};
    $this->currency = \Drupal\mcapi\Entity\Currency::load($value);

    return $this->currency;
  }

  /**
   * {@inheritdoc}
   */
  public function setValue($value, $notify = TRUE) {
    $this->currency = $value;
    // Notify the parent of any changes.
    if ($notify && isset($this->parent)) {
      $this->parent->onChange($this->name);
    }
  }

}
