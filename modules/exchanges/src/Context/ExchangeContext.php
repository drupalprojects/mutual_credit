<?php

namespace Drupal\mcapi_exchanges\Context;

use Drupal\group\GroupMembershipLoader;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Plugin\Context\Context;
use Drupal\Core\Plugin\Context\ContextDefinition;
use Drupal\Core\Plugin\Context\ContextProviderInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Sets the current user's exchange as a context, or for user 1, any exchange
 * from the route.
 *
 * @todo When to use a context and when to use a ContextProvidor?
 */
class ExchangeContext implements ContextProviderInterface {

  use StringTranslationTrait;

  protected $currentUser;
  protected $routeMatch;
  protected $membershipLoader;


  /**
   * Constructs a new ExchangeRouteContext.
   *
   * @param Drupal\Core\Session\AccountInterface $account
   *   The current user session.
   * @param Drupal\group\GroupMembershipLoader $membership_loader
   *   The current user session.
   * @param \Drupal\Core\Routing\RouteMatchInterface $current_route_match
   *   The current route match object.
   */
  public function __construct(AccountInterface $account, GroupMembershipLoader $membership_loader, RouteMatchInterface $current_route_match) {
    $this->currentUser = $account;
    $this->membershipLoader = $membership_loader;
    $this->routeMatch = $current_route_match;
  }

  /**
   * {@inheritdoc}
   */
  public function getRuntimeContexts(array $unqualified_context_ids) {
    // Create an optional context definition for exchange entities.
    $context_definition = new ContextDefinition('entity:group', $this->t('Current exchange'), FALSE);

    // Create a context from the definition and retrieved or created exchange.
    $context = new Context($context_definition, $this->getExchange());

    // Cache this context on the route.
    $cacheability = new CacheableMetadata();
    $cacheability->setCacheContexts(['user', 'route']);

    $context->addCacheableDependency($cacheability);

    $result = [
      'exchange' => $context,
    ];
    return $result;
  }


  /**
   * {@inheritdoc}
   */
  public function getAvailableContexts() {
    debug('ExchangeContext::getAvailableContexts');
    return [];
    return ['exchange' => new Context(new ContextDefinition('entity:group', $this->t('Current exchange')))];
    return $this->getRuntimeContexts([]);
  }


  /**
   * Retrieves the exchange entity from the current route.
   *
   * This will try to load the exchange entity from the route if present. If we are
   * on the exchange add form, it will return a new exchange entity with the exchange
   * type set.
   *
   * @return \Drupal\group\Entity\GroupInterface|null
   *   A group entity of type exchange if found, NULL otherwise.
   */
  public function getExchange() {
    $memberships = $this->membershipLoader->loadByUser($this->currentUser);
    foreach ($memberships as $memship) {
      $group = $memship->getGroup();
      if ($group->type->target_id == 'exchange') {
        return $group;
      }
    }
    // By now only user 1 should be left
    // Try to get a group context from the route.
    if (($group = $this->routeMatch->getParameter('group'))) {
      if ($group instanceof GroupInterface && $group->type->target_id == 'exchange' ) {
        return $group;
      }
    }
    drupal_set_message(t('The current user is not in any exchanges.'), 'warning');
    return NULL;
  }

}
