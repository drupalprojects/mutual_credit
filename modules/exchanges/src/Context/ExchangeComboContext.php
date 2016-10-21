<?php

namespace Drupal\mcapi_exchanges\Context;

use Drupal\group\GroupMembershipLoader;
use Drupal\group\Entity\GroupContent;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Plugin\Context\Context;
use Drupal\Core\Plugin\Context\ContextDefinition;
use Drupal\Core\Plugin\Context\ContextProviderInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Entity\EntityTypeManager;

/**
 * Sets the current group from one of three prioritised contexts
 */
class ExchangeComboContext implements ContextProviderInterface {

  use StringTranslationTrait;

  /**
   * @var Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * @var Drupal\group\GroupMembershipLoader
   */
  protected $membershipLoader;

  /**
   * @var Drupal\Core\Entity\EntityTypeManager;
   */
  protected $entityTypeManager;

  /**
   * @var Drupal\Core\Routing\RouteMatchInterface;
   */
  protected $routeMatch;

  /**
   * Constructs a new GroupRouteContext.
   *
   * @param \Drupal\Core\Session\AccountInterface $current_user
   * @param \Drupal\group\GroupMembershipLoader $membership_loader
   * @param \Drupal\Core\Entity\EntityTypeManager $entity_type_manager
   * @param \Drupal\Core\Entity\EntityTypeManager $route_match
   */
  public function __construct(AccountInterface $current_user, GroupMembershipLoader $membership_loader, EntityTypeManager $entity_type_manager, RouteMatchInterface $route_match) {
    $this->currentUser = $current_user;
    $this->membershipLoader = $membership_loader;
    $this->entityTypeManager = $entity_type_manager;
    $this->routeMatch = $route_match;
  }

  /**
   * {@inheritdoc}
   */
  public function getRuntimeContexts(array $unqualified_context_ids = []) {
    // Create an optional context definition for group entities.
    $context_definition = new ContextDefinition('entity:group',  $this->t('Group from route, user, content'), FALSE);

    // Cache this context on the route.
    $cacheability = new CacheableMetadata();
    $cacheability->setCacheContexts(['route', 'user']);

    // Its not absolutely certain what is the best order of precedence.
    if ($exchange = $this->getExchangeFromRoute()) {
      $context = new Context($context_definition, $exchange);
    }
    elseif($exchange = $this->getExchangeFromUser()) {
      $cacheability->setCacheContexts(['user']);
      $context = new Context($context_definition, $exchange);
    }
    elseif($exchange = $this->getExchangeFromContent())  {
      // Assumes the content is in only one exchange.
      $context = new Context($context_definition, $exchange);
    }
    else $context = new Context($context_definition, NULL);
    $context->addCacheableDependency($cacheability);
    return ['group' => $context];
  }

  /**
   * {@inheritdoc}
   */
  public function getAvailableContexts() {
    $context = new Context(new ContextDefinition('entity:group', $this->t('Group from URL')));
    return ['group' => $context];
  }

  /**
   * Retrieves the group entity from the current route.
   *
   * This will try to load the group entity from the route if present. If we are
   * on the group add form, it will return a new group entity with the group
   * type set.
   *
   * @return \Drupal\group\Entity\GroupInterface|null
   *   A group entity if one could be found or created, NULL otherwise.
   */
  public function getExchangeFromRoute() {
    // See if the route has a group parameter and try to retrieve it.
    if (($group = $this->routeMatch->getParameter('group')) && $group instanceof GroupInterface) {
      if ($group->getGroupType()->id() == 'exchange') {
        return $group;
      }
    }
    // Create a new group to use as context if on the group add form.
    if ($this->routeMatch->getRouteName() == 'entity.group.add_form') {
      $group_type = $this->routeMatch->getParameter('group_type');
      return Group::create(['type' => $group_type->id()]);
    }
  }

  protected function getExchangeFromUser() {
    return $this->groupFromContent($this->membershipLoader->loadByUser($this->currentUser));
  }

  protected function getExchangeFromContent() {
    $route_match = $this->getCurrentRouteMatch();
    $key = $route_match->getRawParameters()->keys()[0];
    if (\Drupal::entityTypeManager()->hasDefinition($key)) {
      $entity = $route_match->getParameter($key);
      if ($entity instanceof ContentEntityInterface){
        return $this->groupFromContent(GroupContent::loadByEntity($entity));
      }
    }
  }

  private function groupFromContent(array $group_contents) {
    foreach ($group_contents as $content) {
      $g = $content->getGroup();
      if ($g->getGroupType()->id() == 'exchange') {
        return $g;
      }
    }
  }

}
