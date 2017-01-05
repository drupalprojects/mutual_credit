<?php

namespace Drupal\mcapi_cc;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceProviderBase;

/**
 * Modifies the language manager service.
 */
class McapiccServiceProvider extends ServiceProviderBase {

  /**
   * {@inheritdoc}
   */
  public function alter(ContainerBuilder $container) {

    debug('delete this message if seen on screen', 'testing');

    // Overrides language_manager class to test domain language negotiation.
    $definition = $container->getDefinition('mcapi_wallet.query.sql');
    $definition->setClass('Drupal\mcapi_cc\Entity\WalletQueryFactory');
  }

}
