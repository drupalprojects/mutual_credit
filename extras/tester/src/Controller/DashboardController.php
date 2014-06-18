<?php

/**
 * @file
 * Contains \Drupal\mcapi_tester\Controller\DashboardController.
 */

namespace Drupal\mcapi_tester\Controller;

use Drupal\Component\Utility\String;
use Drupal\Core\Controller\ControllerBase;
use Drupal\mcapi\Entity\ExchangeInterface;
use Drupal\mcapi\Storage\WalletStorage;

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
  public function pageTitle() {

  }

  /**
   * build a page showing the state of the system.
   * one section for every exchange
   * every exchange has a list of properties, currencies and wallets
   * every wallet shows its owner, balance and limits.
   */
  protected function buildPage() {
    $header = array('#', 'Name', 'Balance', 'Mins', 'Maxes');
    foreach (entity_load_multiple('mcapi_exchange') as $exchange) {
      $id = $exchange->id();
      $page[$id] = array(
        '#title' => 'Exchange: '.$exchange->label(),
        '#description' => ($exchange->status ? 'Open' : 'Closed') . ' Managed by '.($exchange->getOwner()->label()),
        '#type' => 'details',
        '#open' => TRUE,
        'currencies' => array(),
        'wallets' => array()
      );
      $currnames = array();
      foreach ($exchange->currencies->getValue(TRUE) as $item) {
        $currnames[] = $item['entity']->label();
      }
      $page[$id]['currencies'] = array('#markup' => implode(', ', $currnames));
      $wids = \Drupal::EntityManager()->getStorage('mcapi_wallet')->walletsInExchanges(array($id));
      $tbody = array();
      foreach (entity_load_multiple('mcapi_wallet', $wids) as $wallet) {
        $limits = mcapi_limits($wallet);
        $mins = $maxes = $balances = array();
        foreach ($wallet->getSummaries() as $curr_id => $summary) {
          $mins = $limits->mins(TRUE);
          $maxes = $limits->maxes(TRUE);
          $currency = entity_load('mcapi_currency', $curr_id);
          $balances[] = $currency->format($summary['balance']);
        }

        $tbody[$wallet->id()] = array(
        	'id' => l('#'.$wallet->id(), 'wallet/'.$wallet->id()),
          'name' => $wallet->label(),
          'balances' => implode('<br />', $balances),
          'mins' => implode('<br />', $mins),
          'maxes' => implode('<br />', $maxes)
        );
      }
      $page[$id]['wallets'] = array(
      	'#theme' => 'table',
        '#header' => $header,
        '#rows' => $tbody,
        '#attributes' => array('border' => 1)
      );
    }
    return $page;
  }
}
