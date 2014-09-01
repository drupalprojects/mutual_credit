<?php

/**
 * @file
 * Definition of Drupal\mcapi\Plugin\views\access\Wallet.
 * @todo COULDN'T GET THIS TO WORK!!! denies access without even opening this file
 * I don't understand why we need both to alter the route and to check access.
 */

namespace Drupal\mcapi\Plugin\views\access;


use Drupal\views\Annotation\ViewsAccess;
use Drupal\Core\Session\AccountInterface;
use Drupal\views\Plugin\views\access\AccessPluginBase;
use Symfony\Component\Routing\Route;
use Drupal\Core\Language\LanguageInterface;

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
    $storage = \Drupal::entityManager()->getAccess('mcapi_wallet');
    drupal_set_message('Views access-by-wallet not working yet.', 'warning');
    $wallet = '';//TODO get the wallet either from the views argument
    return $storage->checkAccess($wallet, 'view', LanguageInterface::LANGCODE_DEFAULT, $account)
      || $account->hasPermission('access all views');
  }

  /**
   * {@inheritdoc}
   */
  public function alterRouteDefinition(Route $route) {
    //doesn't work, unsurprisingly. at least it doesn't throw an error
    $route->setRequirement('_entity_access', 'mcapi_wallet.view');
  }


  public function summaryTitle() {
    return t('Wallet');
  }

}
