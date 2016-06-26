<?php

namespace Drupal\mcapi\Plugin\views\row;

use Drupal\views\Plugin\views\row\EntityRow;

/**
 * Plugin which performs an entity_view on the resulting object.
 *
 * Most of the code on this object is in the theme function.
 *
 * @ingroup views_row_plugins
 *
 * @ViewsRow(
 *   id = "entity:mcapi_transaction",
 * )
 */
class TransactionRow extends EntityRow {

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['view_mode']['default'] = 'sentence';

    return $options;
  }

}
