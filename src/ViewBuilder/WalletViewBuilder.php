<?php

/**
 * @file
 * Definition of Drupal\mcapi\ViewBuilder\WalletViewBuilder.
 */

namespace Drupal\mcapi\ViewBuilder;

use Drupal\Core\Entity\EntityViewBuilder;
use Drupal\Core\Language\Language;

/**
 * Render controller for nodes.
 */
class WalletViewBuilder extends EntityViewBuilder {


  /**
   * {@inheritdoc}
   */
  public function viewMultiple(array $entities = array(), $view_mode = 'full', $langcode = NULL) {
    foreach ($entities as $wallet) {
      //log view mode is actually a view
      //we might want to remove the views header
      if ($view_mode == 'log') {
        $build_list[] = views_embed_view('wallet_statement', 'embed_1', $wallet->id())
          + array('#langcode' => $this->languageManager->getCurrentLanguage(Language::TYPE_CONTENT)->id);
      }
      else {
        $build_list[] = parent::viewMultiple($entities, $view_mode, $langcode);
      }
    }
    return $build_list;
  }

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


