<?php

/**
 * @file
 * Contains \Drupal\mcapi_tester\Controller\DashboardController.
 * Assumes that mcapi_exchanges module is enabled
 */

namespace Drupal\mcapi_tester\Controller;

use Drupal\Component\Utility\String;
use Drupal\Core\Controller\ControllerBase;
use Drupal\mcapi\Storage\WalletStorage;
use Drupal\mcapi\Exchanges;
use Drupal\mcapi\Entity\Wallet;
use Drupal\mcapi\Entity\Currency;
use Drupal\mcapi_1stparty\Entity\FirstPartyFormDesign;
use Drupal\Core\Template\Attribute;

/**
 * Returns responses for Exchange routes.
 */
class DashboardController extends ControllerBase {

  /**
   * This isn't actually called by the router at the moment
   * @param EntityInterface $mcapi_exchange
   *
   * @return array
   *  A render array
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
   * every wallet shows its holder, balance and limits.
   */
  protected function buildPage() {
    $page['#prefix'] = 'Leave this tab open for reference<br />';
    $header = array('Wallet', 'Name', 'Balance', 'Mins', 'Maxes', 'Edit limits');
    $limiter = \Drupal::service('mcapi_limits.wallet_limiter');
    foreach (Exchange::loadMultiple() as $exchange) {
      $id = $exchange->id();
      $page[$id] = array(
        '#title' => 'Exchange: '.$exchange->label() .($exchange->status->value ? ' (Active)' : ' (Inactive)'),
        '#type' => 'details',
        '#open' => $exchange->status->value,
        'manager' => array(
          '#markup' => 'Managed by '.l(($exchange->getOwner()->label()),$exchange->getOwner()->url())
        ),
        'currencies' => array(
          '#prefix' => '<br />'
        ),
        'open' => array(
          '#prefix' => '<br />',
          '#markup' => 'Open to intertrade: '. ($exchange->open->value ? 'Yes' : 'No'),
        ),
        'wallets' => []
      );
      $currnames = [];
      foreach ($exchange->currencies->referencedEntities() as $entity) {
        $currnames[] = \Drupal::l($entity->label(), 'admin/accounting/currencies/'.$entity->id());
      }
      $page[$id]['currencies']['#markup'] = 'Currencies: '.implode(', ', $currnames);
      $wids = get_mcapi_wallets_in_exchanges(array($id));
      $tbody = [];
      foreach (Wallet::loadMultiple($wids) as $wallet) {
        $limiter->setWallet($wallet);
        $mins = $maxes = $balances = [];
        foreach ($wallet->getSummaries() as $curr_id => $summary) {
          $mins = mins($limiter);
          $maxes = maxes($limiter);
          $currency = Currency::load($curr_id);
          $balances[] = $currency->format($summary['balance']);
        }
        $tbody[$wallet->id()] = array(
          'id' => \Drupal::l('#'.$wallet->id(), 'wallet/'.$wallet->id()),
          'name' => \Drupal::l($wallet->getHolder()->label(), $wallet->getHolder()->url()),
          'balances' => array(
            'data' => array('#markup' => implode('<br />', $balances))
          ),
          'mins' => array(
            'data' => array('#markup' => implode('<br />', $mins))
          ),
          'maxes' => array(
            'data' => array('#markup' => implode('<br />', $maxes))
          ),
          'edit' => \Drupal::l('edit', 'wallet/'.$wallet->id().'/limits'),
        );
      }
      $page[$id]['wallets'] = array(
        '#theme' => 'table',
        '#header' => $header,
        '#rows' => $tbody,
        '#attributes' => new Attribute(array('border' => 1))
      );
      //show a list of forms which will work in this or all exchanges
      $forms = [];
      foreach (FirstPartyFormDesign::loadMultiple() as $editform) {
        if ($editform->exchange == $id || empty($editform->exchange)) {
          $forms[] = \Drupal::l($editform->label(), $editform->path);
        }
      }
      $page[$id]['forms'] = array('#markup' => 'Create transaction: '.implode(', ', $forms));
    }
    return $page;
  }
}


  //@todo move maxes and mins?
function maxes($limiter){
  $limits = $limiter->getLimits();
  $maxes = [];
  foreach (array_keys($limits) as $curr_id) {
    $maxes[$curr_id] = $limiter->max($curr_id);
  }
  return $maxes;
}
function mins($limiter){
  $limits = $limiter->getLimits();
  $mins = [];
  foreach (array_keys($limits) as $curr_id) {
    $mins[$curr_id] = $limiter->min($curr_id);
  }
  return $mins;
}