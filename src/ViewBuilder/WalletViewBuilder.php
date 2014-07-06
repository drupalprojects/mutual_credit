<?php

/**
 * @file
 * Definition of Drupal\mcapi\ViewBuilder\WalletViewBuilder.
 */

namespace Drupal\mcapi\ViewBuilder;

use Drupal\Core\Entity\EntityViewBuilder;
use Drupal\Core\Language\Language;
use Drupal\views\Views;

/**
 * Render controller for wallets.
 */
class WalletViewBuilder extends EntityViewBuilder {


  /**
   * {@inheritdoc}
   * @todo this function is only to accommodate the views embed, so is temp, I think
   */
  public function viewMultiple(array $entities = array(), $view_mode = 'full', $langcode = NULL) {
    //we might want to remove the views header
    //or better, do this with views access
    if ($view_mode == 'list') {
      foreach ($entities as $wallet) {
        //there is also a problem with views_embed_view
        $build_list[] = Views::getView('wallet_statement')->preview('embed_1', array($wallet->id()));
        $build_list[0]['#langcode'] = 'und';
        continue;
        $build_list[] = views_embed_view('wallet_statement', 'embed_1', $wallet->id())
          + array('#langcode' => $this->languageManager->getCurrentLanguage(Language::TYPE_CONTENT)->id);
      }
    }
    else {
      $build_list = parent::viewMultiple($entities, $view_mode, $langcode);
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
      $extra_fields = array('owner', 'stats', 'summary', 'histories', 'balance_bars', 'links');
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


