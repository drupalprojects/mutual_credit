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
    $page['#prefix'] = 'Leave this tab open for reference<br />';
    $header = array('Wallet', 'Name', 'Balance', 'Mins', 'Maxes', 'edit');
    foreach (entity_load_multiple('mcapi_exchange') as $exchange) {
      $id = $exchange->id();
      $page[$id] = array(
        '#title' => 'Exchange: '.$exchange->label() .($exchange->status ? ' (Open)' : ' (Closed)'),
        '#type' => 'details',
        '#open' => $exchange->status,
        'manager' => array(
      	  '#markup' => 'Managed by '.l(($exchange->getOwner()->label()),$exchange->getOwner()->url())
        ),
        'currencies' => array(
          '#prefix' => '<br />'
        ),
        'wallets' => array()
      );
      $currnames = array();
      foreach ($exchange->currencies->getValue(TRUE) as $item) {
        $currnames[] = l($item['entity']->label(), 'admin/accounting/currencies/'.$item['entity']->id);
      }
      $page[$id]['currencies']['#markup'] = 'Currencies: '.implode(', ', $currnames);
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
          'name' => l($wallet->getOwner()->label(), $wallet->getOwner()->url()),
          'balances' => implode('<br />', $balances),
          'mins' => implode('<br />', $mins),
          'maxes' => implode('<br />', $maxes),
          'edit' => l('edit', 'wallet/'.$wallet->id().'/limits'),
        );
      }
      $page[$id]['wallets'] = array(
      	'#theme' => 'table',
        '#header' => $header,
        '#rows' => $tbody,
        '#attributes' => array('border' => 1)
      );
      //show a list of forms which will work in this or all exchanges
      $forms = array();
      foreach (entity_load_multiple('1stparty_editform') as $editform) {
        if ($editform->exchange == $id || empty($editform->exchange)) {
          $forms[] = l($editform->label(), $editform->path);
        }
      }
      $page[$id]['forms'] = array('#markup' => 'Create transaction: '.implode(', ', $forms));
    }
    return $page;
  }
}