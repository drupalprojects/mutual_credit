<?php

namespace Drupal\group_exclusive;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceProviderBase;

/**
 * Overrides the Drupal\user\Access\RegisterAccessCheck service.
 *
 * @deprecated
 */
class GroupExclusiveServiceProvider extends ServiceProviderBase {

  /**
   * {@inheritdoc}
   */
  public function alter(ContainerBuilder $container) {
    //anonymous user creation doesn't need modification
    $definition = $container->getDefinition('access_check.user.register');
    $definition
      ->setClass('\Drupal\group_exclusive\RegisterAccessCheck')
      ->addArgument('group.group_route_context');
  }

}
