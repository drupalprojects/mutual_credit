<?php

/**
 * @file
 * Contains \Drupal\mcapi\Plugin\Derivative\WalletLocalAction.
 */

namespace Drupal\mcapi\Plugin\Derivative;

use Drupal\mcapi\Routing\RouteSubscriber;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Plugin\Discovery\ContainerDeriverInterface;
use Drupal\Core\Routing\RouteProviderInterface;
use Drupal\Component\Plugin\Derivative\DeriverBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides local action definitions to create a wallet for all entities of types configured.
 */
class WalletLocalAction extends DeriverBase implements ContainerDeriverInterface {

  var $config;
  
  var $derivatives = [];

  /**
   * {@inheritDoc}
   */
  public function __construct($configFactory) {
    $this->config = $configFactory->get('mcapi.wallets');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, $base_plugin_id) {
    return new static(
      $container->get('config.factory')
    );
  }

  /**
   * {@inheritdoc}
   * @todo tidy up once the user entity links are routes not paths
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    //add wallet links can go in three locations
    if ($this->config->get('add_link_location') != 'summaries') {
      foreach (RouteSubscriber::walletOwningEntityRoutes() as $canonical_route_name => $bundle) {
        $this->derivatives["mcapi.wallet.add.".$bundle.'.action'] = array(
          'id' => "mcapi.wallet.add.".$bundle.'.action',
          'route_name' => "mcapi.wallet.add.$bundle",//taken from the routesubscriber
          'title' => t('Add Wallet'),
          'appears_on' => [$canonical_route_name]
        );
      }
      foreach ($this->derivatives as &$entry) {
        //don't know if this is needed
        $entry += $base_plugin_definition;
      }
    }
    return $this->derivatives;
  }
}

/* which gives us things like */
//mcapi.wallet.add.user.action:
//  id: mcapi.wallet.add.user.action
//  route_name: mcapi.wallet.add.user
//  title: Add Wallet
//  appears_on:
//    - user.page