<?php

namespace Drupal\mcapi\Access;

/**
 * @file
 * Contains \Drupal\mcapi\Access\TransactionAccessControlHandler.
 */

use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\HttpFoundation\Request;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Access\AccessResult;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines an access controller option for the mcapi_transaction entity.
 */
class TransactionAccessControlHandler extends EntityAccessControlHandler {

  private $routeMatch;
  private $transitionManager;
  static $result;

  public function __construct() {
    $this->routeMatch = \Drupal::RouteMatch();
    $this->transitionManager = \Drupal::Service('mcapi.transition_manager');
  }

  /**
   * {@inheritdoc}
   */
  public function access(EntityInterface $transaction, $transition, $langcode = LanguageInterface::LANGCODE_DEFAULT, AccountInterface $account = NULL, $return_as_object = false) {
    if ($transition == 'transition') {
      $transition = $this->routeMatch->getParameter('transition');
    }
    //the decision is taken by the plugin for the given transition operation
    if ($plugin = $this->transitionManager->getPlugin($transition, $transaction)) {
      //check the states this $op can appear on
      $user = $this->prepareUser($account);
      if ($plugin->accessState($user)) {
        if ($plugin->accessOp($user)) {
          return AccessResult::allowed()->cachePerUser();
        }
      }
    }
    return AccessResult::forbidden()->cachePerUser();
  }


  /**
   * router access callback
   * Find out if the user has access to enough wallets to be able to transact
   *
   * @return AccessResult
   */
  public static function enoughWallets($account = NULL) {
    Self::$result;
    if (!Self::$result) {
      if (!$account){
        $account = \Drupal::currentUser();
      }
      $walletStorage = \Drupal::entityManager()->getStorage('mcapi_wallet');
      $payin = $walletStorage->walletsUserCanActOn('payin', $account);
      $payout = $walletStorage->walletsUserCanActOn('payout', $account);
      //there must be at least one wallet in each (and they must be different!)
      Self::$result = ($payin && $payout && count(array_unique(array_merge($payin, $payout))) > 1) ?
        AccessResult::allowed()->cachePerUser() :
        AccessResult::forbidden()->cachePerUser();
    }
    return Self::$result;
  }


}
