<?php

/**
 * @file
 * Contains \Drupal\mcapi\Controller\WalletAutocompleteController.
 */

namespace Drupal\mcapi\Controller;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Drupal\mcapi\Entity\Wallet;
use Drupal\Core\DependencyInjection\DependencySerializationTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityManagerInterface;

/**
 * Returns responses for wallet autocomplete fields on the transaction form
 */
class WalletAutocompleteController implements ContainerInjectionInterface{

  use DependencySerializationTrait;

  private $entitymanager;
  private $currentUser;
  private $role;

  function __construct(EntityManagerInterface $entityManager, $account, $routeMatch) {
    $this->entityManager = $entityManager;
    $this->currentUser = $account;
    $this->role = $routeMatch->getParameter('role');
  }

  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.manager'),
      $container->get('current_user'),
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
    //return only the wallets which are both permitted and meet the filter criteria
    $walletStorage =  $this->entityManager->getStorage('mcapi_wallet');
    $results = $walletStorage->filter($conditions);
    if ($this->role != 'null') {
      $results = array_intersect(
        $results,
        $walletStorage->walletsUserCanTransit($this->role, $this->currentUser)
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
          'value' => $wallet->label(NULL, FALSE),//maybe shorter
          'label' => $wallet->label(NULL, TRUE)
          //both labels should end with the #wid which is needed for parsing later
        ];
      }
    }
    return new JsonResponse($json);
  }

}
