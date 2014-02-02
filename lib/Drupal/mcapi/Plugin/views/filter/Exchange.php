<?php

/**
 * @file
 * Definition of Drupal\node\Plugins\views\filter\Exchange.
 */

namespace Drupal\mcapi\Plugin\views\filter;

use Drupal\Component\Annotation\PluginID;
use Drupal\views\Plugin\views\filter\FilterPluginBase;

/**
 * Filter transactions by which exchange they are in
 *
 * @ingroup views_filter_handlers
 *
 * @PluginID("mcapi_current_exchange")
 */
class Exchange extends FilterPluginBase {


  public function query($use_groupby = FALSE) {

    $table = $this->ensureMyTable();
    $this->query->addWhere(0, "$table.exchange", array_keys(referenced_exchanges()));
  }

  //there is nothing to expose
  public function showExposeButton(&$form, &$form_state) {

  }

}
