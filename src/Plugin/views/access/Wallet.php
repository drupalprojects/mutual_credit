<?php

/**
 * @file
 * Definition of Drupal\mcapi\Plugin\views\access\Wallet.
 *
 * @deprecated since there seems to be no good way to get the wallet from the views arguments
 */

namespace Drupal\mcapi\Plugin\views\access;

use Drupal\Core\Session\AccountInterface;
use Drupal\views\Plugin\views\access\AccessPluginBase;
use Symfony\Component\Routing\Route;
use Drupal\Core\Language\LanguageInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Access plugin that provides access control according to which wallet is being viewed
 * I can't get this to work any which way.
 * Consider adding no_ui to the definition if we do get it working
 *
 * @ingroup views_access_plugins
 *
 * @ViewsAccess(
 *   id = "wallet",
 *   title = @Translation("Wallet"),
 *   help = @Translation("Granted according to each wallet's settings."),
 *   base = {"mcapi_wallet", "mcapi_transaction", "mcapi_transactions_index"},
 * )
 */

class Wallet extends AccessPluginBase {

  /**
   * {@inheritdoc}
   * @todo this isn't working well now because the views route doesn't know the wallet is an entity argument
   * therefore we are using an embed instead, managing the route ourselves
   */
  public function access(AccountInterface $account) {
    $access = $this->entityManager->getAccessControlHandler('mcapi_wallet');
    //load the view
    return $access->checkAccess($wallet, 'view', LanguageInterface::LANGCODE_DEFAULT, $account)
      || $account->hasPermission('access all views');
  }


  /**
   * {@inheritdoc}
   */
  public function alterRouteDefinition(Route $route) {
    //doesn't work, unsurprisingly. at least it doesn't throw an error
    $route->setRequirement('_entity_access', 'mcapi_wallet.details');
  }

  /**
   * {@inheritdoc}
   */
  public function summaryTitle() {
    return $this->t('DO NOT USE');
    return $this->t('Wallet from url');
  }

}
