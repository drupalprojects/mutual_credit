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
    //This means we prevent ordinary user registration
    if ($exclusive_memberships = group_exclusive_members()) {
      // Replace the anon user registration form with one designed for exchanges.
      $collection->get('user.register')
        ->setPath('group/{group}/user/register')
        ->setOption('parameters', ['group' => ['type' => 'entity:group']]);
      // Change the path of admin/people/create and access to allow only group
      // admins to create users.
      if ($route = $collection->get('user.admin_create')) {
        $route->setRequirements([]);return;
        //this new path is returning page not found
        $route
          ->setPath('/group/{group}/people/create')
          ->setDefault('group', 'true') // This is a mandatory parameter in UrlGenerator::doGenerate
          ->setRequirements([
            '_group_permission' => 'administer members',
            '_group_type' => implode(';', $exclusive_memberships),
            '_method' => 'GET|POST',
          ])
          ->setOption('parameters', ['group' => ['type' => 'entity:group']]);
      }
    }
  }

}
