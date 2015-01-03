<?php

/**
 * @file
 * Definition of Drupal\mcapi_exchanges\Plugins\views\filter\Exchange.
 */

namespace Drupal\mcapi_exchanges\Plugin\views\filter;

use Drupal\Component\Annotation\PluginID;
use Drupal\views\Plugin\views\filter\FilterPluginBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\mcapi\Mcapi;

/**
 * Filter transactions by which exchange they are in
 *
 * @ingroup views_filter_handlers
 *
 * @ViewsFilter("mcapi_current_exchange")
 */
class Exchange extends FilterPluginBase {


  public function query($use_groupby = FALSE) {

    $table = $this->ensureMyTable();
    $this->query->addWhere(0, "$table.exchange", array_keys(Exchanges::in(NULL, TRUE)));
  }

  //there is nothing to expose
  public function showExposeButton(&$form, FormStateInterface $form_state) {

  }

}
