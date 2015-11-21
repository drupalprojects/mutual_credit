<?php

/**
 * @file
 * Contains \Drupal\mcapi\Controller\WalletAutocompleteController.
 */

namespace Drupal\mcapi\Controller;

use Drupal\mcapi\Entity\Wallet;
use Drupal\Core\DependencyInjection\DependencySerializationTrait;
use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Returns responses for wallet autocomplete fields on the transaction form
 */
class WalletAutocompleteController extends ControllerBase{

  use DependencySerializationTrait;

  /**
   * helps filter for wallets according to which actions the current user can do on them.
   */
  private $role;

  function __construct($routeMatch) {
    $this->role = $routeMatch->getParameter('role');
  }

  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('current_route_match')
    );
  }

  /*
   * get a list of wallet names and ids
   */
  function autocomplete(Request $request) {
    $wids = $this->getWalletIds($request);
    return $this->returnWallets($wids);
  }

  /**
   * Get a list of wallet ids from params in the current route
   * 
   * @param Request $request
   * @return integer[]
   */
  protected function getWalletIds(Request $request) {
    //there are three different ways offered here, none of which is perfect
    //because of the different ways that wallet names can be construed
    $results = [];
    $conditions = ['intertrading' => FALSE];

    $string = $request->query->get('q');

    //there is no need to handle multiple values because the javascript of the widget
    //handles all stuff before the last comma.
    if (is_numeric($string)) {
      $conditions['wid'] = [$string];
    }
    //deal with the case where a wid has been entered with a hash
    elseif (substr($string, 0, 1) == '#' && is_numeric($num = substr($string, 1))) {
      $conditions['wid'] = $num;
    }
    else {
      $conditions['fragment'] = $string;
    }
    $walletStorage = $this->entityManager()->getStorage('mcapi_wallet');
    //return only the wallets which are both permitted and meet the filter criteria
    $results = $walletStorage->filter($conditions);
    if ($this->role) {
      $walletperm = $this->role == 'payer'
          ? Wallet::OP_PAYOUT
          : Wallet::OP_PAYIN;
      $results = array_intersect(
        $results,
        $walletStorage->walletsUserCanActOn($walletperm, $this->currentUser())
      );
    }
    return $results;
  }

  protected function returnWallets($wids) {
    $json = [];
    if (empty($wids)) {
      $json[] = [
        'value' => '',
        'label' => '--'.t('No matches').'--'
      ];
    }
    else {
      foreach (Wallet::loadMultiple($wids) as $wallet) {
        $json[] = [
          'value' => $wallet->label(NULL, FALSE) .' #'.$wallet->id(),
          'label' => $wallet->label(NULL, TRUE)
          //both labels should end with the #wid which is needed for parsing later
        ];
      }
    }
    return new JsonResponse($json);
  }

}
