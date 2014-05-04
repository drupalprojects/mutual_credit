<?php

/**
 * @file
 * Definition of Drupal\mcapi\Plugin\views\field\OwnerType.
 */

namespace Drupal\mcapi\Plugin\views\field;

use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\Plugin\views\field\Standard;
use Drupal\views\Plugin\views\display\DisplayPluginBase;
use Drupal\Component\Annotation\PluginID;
use Drupal\views\ResultRow;

/**
 * Field handler to link the transaction description to the transaction itself
 *
 * @ingroup views_field_handlers
 *
 * @PluginID("mcapi_owner_type")
 */
class OwnerType extends Standard {

  private $labels;

  function __construct() {
    $defs = \Drupal::EntityManager()->getDefinitions();
    foreach ($defs as $id => $def) {
      $this->labels[$id] = $def['label'];
    }
    $this->labels['system'] = t('System');
  }

  /**
   * {@inheritdoc}
   */
  function render(ResultRow $values) {
    return $this->labels[$values->{$this->field_alias}];
  }

}
