<?php

/**
 * @file
 * Definition of Drupal\mcapi\ViewBuilder\WalletViewBuilder.
 */

namespace Drupal\mcapi\ViewBuilder;

use Drupal\Core\Entity\EntityViewBuilder;
use Drupal\Core\Language\Language;
use Drupal\views\Views;
use Drupal\mcapi\CurrencyInterface;
use Drupal\Core\Url;

/**
 * Render controller for wallets.
 */
class WalletViewBuilder extends EntityViewBuilder {

  /**
   * {@inheritdoc}
   * For multiple nice wallets see theme callback 'mcapi_wallets'
   */
  public function buildComponents(array &$build, array $entities, array $displays, $view_mode, $langcode = NULL) {
    module_load_include('inc', 'mcapi', 'src/ViewBuilder/wallet');
    //add the field api fields and properties
    parent::buildComponents($build, $entities, $displays, $view_mode, $langcode);
    //add the extraFields
    $extra = $this->extraFields();
    foreach ($entities as $id => $wallet) {
      foreach ($displays['mcapi_wallet']->getComponents() as $exfield =>$props) {
        //fortunately all of these functions take the same one argument
        $function = 'mcapi_'.$exfield;
        if ($exfield == 'summaries') debug('summaries is still here');
        if (function_exists($function))//this is TEMP for the 'summaries' which should be gone next install
        $build[$id][$exfield] = $function($wallet);
        $build[$id][$exfield]['#weight'] = $props['weight'];
      }
      if ($view_mode != 'default') {
        //this puts the wallet name above and the wallet links below
        $build[$id]['#theme_wrappers'] = ['wallet_wrapper'];
      }
    }
    $build['#attached'] = array(
      'library' => array(
        'mcapi/mcapi.wallets',
        'mcapi/mcapi.gchart'
      )
    );
    $build['#sorted'] = FALSE;
  }
  
  /**
   * Populate hook_extra_fields in a way that is consistent with this objects ability to render them
   * @return []
   */
  public static function extraFields() {
    return array(
      'stats' => array(
        'label' => t('Trading stats'), 
        'description' => t('Grid showing trading stats for all currencies'),
      ),
      'balances' => array(
        'label' => t('Balance(s)'),
        'description' => t('Small thingy showing balances of all currencies'),
      ),
      'histories' => array(
        'label' => t('History chart(s)'),
        'description' => t('One line chart per currency showing balance over time.'),
      ), 
      'balance_bars' => array(
        'label' => t('Balance bar charts'),
        'description' => t('One barchart per currency showing incoming and outgoing volumes'),
      )
    );
  }

 
}
