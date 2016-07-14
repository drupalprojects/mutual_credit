<?php

namespace Drupal\mcapi_exchanges;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceProviderBase;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Overrides the Drupal\user\Access\RegisterAccessCheck service.
 */
class McapiExchangesServiceProvider extends ServiceProviderBase {

  /**
   * {@inheritdoc}
   */
  public function alter(ContainerBuilder $container) {
    $definition = $container->getDefinition('access_check.user.register');
    $definition
      ->setClass('Drupal\mcapi_exchanges\Overrides\RegisterAccessCheck')
      ->addArgument('group.group_route_context');

  }

}
