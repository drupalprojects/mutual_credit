<?php

namespace Drupal\mcapi_exchanges\Plugin\views\filter;

use Drupal\views\Plugin\views\filter\FilterPluginBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Filter transactions by which exchange they are in.
 *
 * @ingroup views_filter_handlers
 *
 * @ViewsFilter("mcapi_current_exchange")
 *
 * @todo This will probably be provided by group module
 */
class Exchange extends FilterPluginBase {

  /**
   *
   */
  public function query($use_groupby = FALSE) {

    $table = $this->ensureMyTable();
    $this->query->addWhere(0, "$table.exchange", array_keys(Exchanges::memberOf(NULL, TRUE)));
  }

  /**
   * There is nothing to expose.
   */
  public function showExposeButton(&$form, FormStateInterface $form_state) {

  }

}
