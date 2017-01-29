<?php

namespace Drupal\mcapi\Plugin\Field;

use Drupal\mcapi\Entity\Currency;
use Drupal\Core\TypedData\DataDefinitionInterface;
use Drupal\Core\TypedData\TypedDataInterface;
use Drupal\Core\Field\EntityReferenceFieldItemList;

/**
 * A computed property for the loaded currency in a worth field.
 *
 * Required settings (below the definition's 'settings' key) are:
 *  - currency source: The currency.
 */
class CurrencyComputedProperty extends EntityReferenceFieldItemList {

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
    if (empty($this->currency)) {
      $source_prop = $this->definition->getSetting('currency source');
      $parentWorthItem = $this->getParent();
      /* @var $parentWorthItem Drupal\mcapi\Plugin\Field\FieldType\WorthItem */
      $value = $parentWorthItem->{$source_prop};
      $this->currency = Currency::load($value);
    }
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
