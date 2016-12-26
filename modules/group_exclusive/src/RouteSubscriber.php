<?php

namespace Drupal\group_exclusive;

use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;

/**
 * Ensure a group is passed to the user.admin_create path.
 */
class RouteSubscriber extends RouteSubscriberBase {

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection) {
    if ($exclusive_memberships = group_exclusive_members()) {
      // Modify the anon user registration form with one designed for groups.

      $collection->get('user.register')
        ->setPath('group/{group}/user/register')
        ->setOption('parameters', ['group' => ['type' => 'entity:group']])
        ->setRequirement('_group_type', implode(';', $exclusive_memberships));

      // Make the route for admins to create users inaccessible
      $collection->get('user.admin_create')->setRequirements([]);

      // Restrict this module's new user creation route to the exclusive types.
      $collection->get('group.user.admin_create')
        ->setRequirement('_group_type', implode(';', $exclusive_memberships))
        ->setRequirement('_group_permission', 'administer members');

    }
  }

}
