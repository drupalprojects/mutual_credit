<?php

namespace Drupal\mcapi_exchanges;

use Drupal\mcapi_exchanges\Context\MyExchangeContext;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Determines access to routes based on login status of current user.
 */
class MyExchangePermission implements AccessInterface {

  /**
   * @var Drupal\mcapi_exchanges\Context\MyExchangeContext
   */
  protected $context;

  /**
   * Constructor
   *
   * @param MyExchangeContext $context
   */
  function __construct(MyExchangeContext $context) {
    $this->context = $context;
  }

  /**
   * Checks access.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The currently logged in account.
   * @param Drupal\Core\Routing\RouteMatchInterface $routeMatch
   *   The current route
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  public function access(AccountInterface $account, RouteMatchInterface $routeMatch) {
    $exchange = $this->context->getRuntimeContexts([])['exchange']->getContextValue();
    if (!$exchange) {
      return AccessResult::forbidden()->cachePerUser();
    }
    return \Drupal\group\Access\GroupAccessResult::allowedIfHasGroupPermission(
      $exchange,
      $account,
      $routeMatch->getRouteObject()->getRequirement('_exchange_permission')
    )->cachePerUser();
  }

}
