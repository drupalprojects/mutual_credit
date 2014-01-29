<?php

/**
 * @file
 * Definition of Drupal\mcapi\Plugin\views\access\Wallet.
 * COULDN'T GET THIS TO WORK!!!
 */

namespace Drupal\mcapi\Plugin\views\access;


use Drupal\views\Annotation\ViewsAccess;
use Drupal\Core\Session\AccountInterface;
use Drupal\views\Plugin\views\access\AccessPluginBase;
use Symfony\Component\Routing\Route;

/**
 * Access plugin that provides access control according to which wallet is being viewed
 *
 * @ingroup views_access_plugins
 *
 * @ViewsAccess(
 *   id = "wallet",
 *   title = @Translation("Wallet"),
 *   help = @Translation("Granted according to each wallet's settings.")
 * )
 */
class Wallet extends AccessPluginBase {


  /**
   * {@inheritdoc}
   */
  public function access(AccountInterface $account) {
    //NOT TRIED
    return TRUE;
    //return user_access($this->options['perm'], $account) || user_access('access all views', $account);
  }

  /**
   * {@inheritdoc}
   */
  public function alterRouteDefinition(Route $route) {
    //$route->setRequirement('_permission', 'access content');

    //doesn't work, unsurprisingly. at least it doesn't throw an error
    $route->setRequirement('_entity_access', 'mcapi_wallet.view');
  }


  public function summaryTitle() {
    return t('Wallet');
  }

}
