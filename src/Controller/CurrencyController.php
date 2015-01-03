<?php

/**
 * @file
 * Contains \Drupal\mcapi\Controller\CurrencyController.
 */

namespace Drupal\mcapi\Controller;

use Drupal\Component\Utility\String;
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
   *  An array suitable for drupal_render().
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
    return t('Activity in !currency', array('!currency' => String::checkPlain($mcapi_currency->label())));
  }

  /**
   * Builds an exchange page render array.
   *
   * @param EntityInterface $mcapi_currency
   *
   * @return array
   *   An array suitable for drupal_render().
   */
  protected function buildPage(CurrencyInterface $mcapi_currency) {
    return array(
      'exchanges' => $this->entityManager()
        ->getViewBuilder('mcapi_currency')
        ->view($mcapi_currency)
    );
  }
}
