<?php

/**
 * @file
 * Contains \Drupal\mcapi\ExchangeViewBuilder.
 */

namespace Drupal\mcapi;

use Drupal\Core\Entity\EntityViewBuilder;

/**
 * Base class for entity view controllers.
 */
class ExchangeViewBuilder extends EntityViewBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildContent(array $entities, array $displays, $view_mode, $langcode = NULL) {
    parent::buildContent($entities, $displays, $view_mode, $langcode);
    foreach ($entities as $exchange) {
      $exchange->content += array(
      	'#markup' => 'This page needs to show some basic info about the exchange. Its members, its currencies, its admin and managers. Number of transactions ever and transaction volume per currency.'
      );
    }
  }

}
