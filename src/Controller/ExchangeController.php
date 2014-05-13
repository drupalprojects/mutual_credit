<?php

/**
 * @file
 * Contains \Drupal\mcapi\Controller\ExchangeController.
 */

namespace Drupal\mcapi\Controller;

use Drupal\Component\Utility\String;
use Drupal\Core\Controller\ControllerBase;
use Drupal\mcapi\ExchangeInterface;

/**
 * Returns responses for Exchange routes.
 */
class ExchangeController extends ControllerBase {

  /**
   * This isn't actually called by the router at the moment
   * @param EntityInterface $mcapi_exchange
   *
   * @return array
   *  An array suitable for drupal_render().
   */
  public function page(ExchangeInterface $mcapi_exchange) {

    return $this->buildPage($mcapi_exchange);
  }

  /**
   * The _title_callback for the mcapi.exchange.view route.
   *
   * @param EntityInterface $mcapi_exchange
   *
   * @return string
   *   The page title.
   */
  public function pageTitle(ExchangeInterface $mcapi_exchange) {
    return String::checkPlain($mcapi_exchange->label());
  }

  /**
   * Builds an exchange page render array.
   *
   * @param EntityInterface $mcapi_exchange
   *
   * @return array
   *   An array suitable for drupal_render().
   */
  protected function buildPage(ExchangeInterface $mcapi_exchange) {
    return array(
      'exchanges' => $this->entityManager()
        ->getViewBuilder('mcapi_exchange')
        ->view($mcapi_exchange)
    );
  }
}
