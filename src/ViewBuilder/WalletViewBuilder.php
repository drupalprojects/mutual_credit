<?php

/**
 * @file
 * Definition of Drupal\mcapi\ViewBuilder\WalletViewBuilder.
 */

namespace Drupal\mcapi\ViewBuilder;

use Drupal\Core\Entity\Display\EntityViewDisplayInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityViewBuilder;

/**
 * Render controller for nodes.
 */
class WalletViewBuilder extends EntityViewBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildComponents(array &$build, array $entities, array $displays, $view_mode, $langcode = NULL) {
    module_load_include('inc', 'mcapi');
    $display = $displays['mcapi_wallet'];
    foreach ($entities as $wid => $wallet) {
      $extra_fields = array('owner', 'stats', 'summaries', 'histories', 'balance_bars', 'links');
      foreach ($extra_fields as $exfield) {
        if ($display->getComponent($exfield)) {
          $function = 'show_wallet_'.$exfield;
          //fortunately all of these functions take the same one argument
          $build[$wid][$exfield] = $function($wallet);
        }
      }
    }
  }
}


