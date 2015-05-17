<?php

/**
 * @file
 * Contains \Drupal\mcapi\Controller\CurrencyController.
 *
 * @todo make this work
 */

namespace Drupal\mcapi\Controller;

use Drupal\Component\Utility\SafeMarkup;
use Drupal\Core\Controller\ControllerBase;
use Drupal\mcapi\CurrencyInterface;

/**
 * Returns responses for Exchange routes.
 */
class CurrencyController extends ControllerBase {

  /**
   * This isn't actually called by the router at the moment
   * @param EntityInterface $mcapi_currency
   *
   * @return array
   *  A render array
   */
  public function page(CurrencyInterface $mcapi_currency) {
    return $this->buildPage($mcapi_currency);
  }

  /**
   * The _title_callback for the mcapi.exchange.view route.
   *
   * @param EntityInterface $mcapi_currency
   *
   * @return string
   *   The page title.
   */
  public function pageTitle(CurrencyInterface $mcapi_currency) {
    return SafeMarkup::checkPlain($mcapi_currency->label());
  }
}
