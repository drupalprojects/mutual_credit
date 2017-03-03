<?php

namespace Drupal\group_exclusive\Context;

use Drupal\group\Context\GroupRouteContextTrait;
use Drupal\group\GroupMembershipLoader;
use Drupal\group\Entity\GroupContent;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Plugin\Context\Context;
use Drupal\Core\Plugin\Context\ContextDefinition;
use Drupal\Core\Plugin\Context\ContextProviderInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Sets the current group from one of three prioritised contexts
 */
class ExclusiveGroupContext implements ContextProviderInterface {

  use GroupRouteContextTrait;
  use StringTranslationTrait;

  /**
   * @var Drupal\Core\Session\AccountInterface
   */
  protected $config;

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
   * Constructs a new GroupRouteContext.
   *
   * @param \Drupal\Core\Session\AccountInterface $current_user
   * @param \Drupal\group\GroupMembershipLoader $membership_loader
   * @param \Drupal\Core\Entity\EntityTypeManager $entity_type_manager
   */
  public function __construct(ConfigFactory $config_factory, AccountInterface $current_user, GroupMembershipLoader $membership_loader, EntityTypeManager $entity_type_manager) {
    $this->config = $config_factory->get('group_exclusive.settings');
    $this->currentUser = $current_user;
    $this->membershipLoader = $membership_loader;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function getRuntimeContexts(array $unqualified_context_ids = []) {
    //foreach ($this->config('bundles') as $bundle) {
      // Create an optional context definition for group entities.
      $context_definition = new ContextDefinition('entity:group',  $this->t('Exclusive group'), FALSE, FALSE);


      // Not 100% sure if this caching logic is correct...
      if ($group = $this->getGroupFromRoute()) {
        $context = new Context($context_definition, [$group->id() => $group]);
      }
      elseif($groups = $this->getGroupsFromUser()) {
        $cacheability->setCacheContexts(['user']);
        $context = new Context($context_definition, $groups);
      }
      elseif($groups = $this->getGroupsFromContent())  {
        $context = new Context($context_definition, $groups);
      }
      else $context = new Context($context_definition, []);

    // Cache this context on the route.
    $cacheability = new CacheableMetadata();
    $cacheability->setCacheContexts(['route']);
    $context->addCacheableDependency($cacheability);
    return ['exclusive_group' => $context];
  }

  /**
   * {@inheritdoc}
   */
  public function getAvailableContexts() {
    $context = new Context(new ContextDefinition('entity:group', $this->t('Group from URL')));
    return ['exclusive_group' => $context];
  }

  protected function getGroupsFromUser() {
    $groups = $this->groupsFromContent($this->membershipLoader->loadByUser($this->currentUser));
    return $groups;
  }

  protected function getGroupsFromContent() {
    $groups = [];
    $route_match = $this->getCurrentRouteMatch();
    $key = $route_match->getRawParameters()->keys()[0];
    if ($this->entityTypeManager->hasDefinition($key)) {
      $entity = $route_match->getParameter($key);
      if ($entity instanceof ContentEntityInterface){
        $content = GroupContent::loadByEntity($entity);
        $groups = $this->groupsFromContent($content);
      }
    }
    return $groups;
  }

  private function groupsFromContent(array $group_contents) {
    $groups = [];
    foreach ($group_contents as $content) {
      $g = $content->getGroup();
      $groups[$g->id()] = $g;
    }
    return $groups;
  }

}
