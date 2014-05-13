<?php

/**
 * @file
 * Definition of Drupal\mcapi\Plugins\views\filter\ExchangeVisibility.
 */

namespace Drupal\mcapi\Plugin\views\filter;

use Drupal\views\Plugin\views\filter\FilterPluginBase;
use Drupal\Component\Annotation\PluginID;

/**
 * Filter by the exchange's privacy setting
 *
 * @ingroup views_filter_handlers
 *
 * @ViewsFilter("exchange_visibility")
 */
class ExchangeVisibility extends FilterPluginBase {

  public function getValueOptions() {
    //oops how do we get an exchange entity so as to access the privacy_options function?
    $exchange = current(references_exchanges());
    $this->value_options = $exchange->visibility_options();
  }

  public function query($use_groupby = FALSE) {
    //only show private exchanges to admins
    if (!\Drupal::currentUser()->hasPermission('manage mcapi')) {
      $this->query->addWhere(0, "$table.visibility", 'private', '<>');
    }
    //don't show restricted exchanges to anon
    if (!\Drupal::currentUser()->id()) {
      $this->query->addWhere(0, "$table.visibility", 'restricted', '<>');
    }
  }

  //there is nothing to expose
  public function showExposeButton(&$form, &$form_state) {}
}
