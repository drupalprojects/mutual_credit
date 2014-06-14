<?php

/**
 * @file
 * Contains \Drupal\mcapi_tester\Controller\DashboardController.
 */

namespace Drupal\mcapi\Controller;

use Drupal\Component\Utility\String;
use Drupal\Core\Controller\ControllerBase;
use Drupal\mcapi\Entity\ExchangeInterface;

/**
 * Returns responses for Exchange routes.
 */
class DashboardController extends ControllerBase {

  /**
   * This isn't actually called by the router at the moment
   * @param EntityInterface $mcapi_exchange
   *
   * @return array
   *  An array suitable for drupal_render().
   */
  public function page() {

    return $this->buildPage();
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
   * build a page showing the state of the system.
   * one section for every exchange
   * every exchange has a list of properties, currencies and wallets
   * every wallet shows its owner, balance and limits.
   */
  protected function buildPage(ExchangeInterface $mcapi_exchange) {
    foreach (entity_load_multiple('mcapi_exchange') as $exchange) {
      $page[$exchange->id()] = array(
        '#title' => 'Exchange: '.$exchange->label(),
        '#description' => ($exchange->status ? 'open' : 'closed') . ' Managed by '.user_load($exchange->manager->value)->name,
        '#type' => 'details',
        'currencies' => array(),
        'wallets' => array()
      );
      foreach ($exchange->currencies->getValue(TRUE) as $item) {
        $currency = $item->entity;
        $page[$exchange->id()]['currencies'] = $currency->label();
        //enough for now
      }
    }
    return $page;
  }
}
