<?php

/**
 * @file
 * Definition of Drupal\mcapi\Plugin\views\field\Worth.
 */

namespace Drupal\mcapi\Plugin\views\field;

//TODO which of these are actually needed?
use Drupal\views\Plugin\views\area\Result;
use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\Plugin\views\display\DisplayPluginBase;
use Drupal\views\ResultRow;
use Drupal\views\ViewExecutable;
use Drupal\Component\Annotation\PluginID;

/**
 * When we look at transaction index table, we need to view one worth at a time
 * deriving the currency from a separate field
 *
 *
 * @todo This handler should use entities directly.
 *
 * @ingroup views_field_handlers
 *
 * @PluginID("worth")
 */
class Worth extends FieldPluginBase {

  function __construct() {
    //define the additional field here: currode
  }

  public function query() {
    //need to ensure the currency code field is included from the index table
    $this->ensureMyTable();
   $this->add_additional_fields();
  }

  /**
   * {@inheritdoc}
   */
  public function render(ResultRow $values) {
    print_r($this->aliases);
    die('//TODO render the currency in \mcapi\Plugin\views\field\Worth.php');
    return $this->getEntity($values)->worths->getString();
  }

}
