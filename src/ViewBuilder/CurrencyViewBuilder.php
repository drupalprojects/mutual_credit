<?php

/**
 * @file
 * Contains \Drupal\mcapi\ViewBuilder\CurrencyViewBuilder.
 */

namespace Drupal\mcapi\ViewBuilder;

use Drupal\Core\Entity\EntityViewBuilder;

/**
 * Base class for entity view controllers.
 */
class CurrencyViewBuilder extends EntityViewBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildContent(array $entities, array $displays, $view_mode, $langcode = NULL) {
    parent::buildContent($entities, $displays, $view_mode, $langcode);
    foreach ($entities as $currency) {
      //instead of using <br> here, isn't there a nicer way to make each render array into a div or something like that
      $currency->content['whatever'] = array();

      //TEMP!!!
      $currency->content['placeholder_text'] = array(
        '#weight' => -1,
      	'#markup' => "View mode: <strong>$view_mode</strong> <br />We need a nice statistical display of all the transactions done in a currency, what categories it is used in, proportion of intertrading transactions, greco index, trades/volume per week, average satisfaction ratings, etc whatever, it might be overriden in exchanges module with more info still"
      );
    }
  }

}
