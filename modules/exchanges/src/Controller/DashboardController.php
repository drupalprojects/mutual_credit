<?php

/**
 * @file
 * Contains \Drupal\mcapi_exchanges\Controller\DashboardController.
 */

namespace Drupal\mcapi_exchanges\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\mcapi\Entity\Wallet;
use Drupal\mcapi\Entity\Currency;
use Drupal\mcapi_forms\Entity\FirstPartyFormDesign;
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
          '#markup' => 'Managed by '. $exchange->getOwner()->toLink()
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
          'name' => $wallet->getHolder()->toLink(),
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

  /**admin/modules
   * Get all the wallet ids in given exchanges.
   * Can also be done with Wallet::filter() but this is more efficient.
   * maybe not worth it if this is only used once, in any case the index table is needed for views.
   * Each wallet holder should have a required entity reference field pointing to exchanges.
   *
   * @param array $exchange_ids
   *
   * @param array $intertrading
   *   TRUE if _intertrading wallets are included
   *
   * @return integer[]
   *   the non-orphaned wallet ids from the given exchanges
   *
   * @todo refactor this for OG
   */
  function get_mcapi_wallets_in_exchanges(array $exchange_ids) {
    $query = db_select('og_membership', 'g')
      ->fields('g', array('etid'))
      ->condition('g.group_type', 'mcapi_exchange')
      ->condition('g.entity_type', 'mcapi_wallet');
    if ($exchange_ids) {
      $query->condition('g.gid', $exchange_ids);
    }
    if (!$intertrading) {
      $query->join('mcapi_wallet', 'w', 'w.wid = g.etid');
      $query->condition('w.name', '_intertrading', '<>');
    }
    return $query->execute()->fetchCol();
  }