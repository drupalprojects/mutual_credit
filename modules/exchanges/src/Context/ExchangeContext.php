<?php

namespace Drupal\mcapi_exchanges\Context;

use Drupal\group\Context\GroupRouteContextTrait;
use Drupal\group\GroupMembershipLoader;
use Drupal\group\Entity\GroupContent;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Plugin\Context\Context;
use Drupal\Core\Plugin\Context\ContextDefinition;
use Drupal\Core\Plugin\Context\ContextProviderInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Sets the current group from one of three prioritised contexts
 */
class ExchangeContext implements ContextProviderInterface {

  use GroupRouteContextTrait;
  use StringTranslationTrait;

  /**
   * @var Drupal\group\GroupMembershipLoader
   */
  protected $membershipLoader;

  /**
   * Constructs a new GroupRouteContext.
   *
   * @param \Drupal\group\GroupMembershipLoader $membership_loader
   */
  public function __construct(GroupMembershipLoader $membership_loader) {
    $this->membershipLoader = $membership_loader;
  }

  /**
   * {@inheritdoc}
   */
  public function getRuntimeContexts(array $unqualified_context_ids = []) {
    $context_definition = new ContextDefinition('entity:group',  $this->t('Exchange'), FALSE, FALSE);

    if ($membership = mcapi_exchange_current_membership()) {
      $exchange = $membership->getGroup();
    }
    else {
      $exchange = NULL;
    }

    //TEMP
    //$group = \Drupal\group\Entity\Group::load(2);

    $context = new Context($context_definition, $exchange);
    //mdump($context);die();
    // Cache this context on the route.
    $cacheability = new CacheableMetadata();
    $cacheability->setCacheContexts(['route']);
    $context->addCacheableDependency($cacheability);
    return ['group' => $context];
  }

  /**
   * {@inheritdoc}
   */
  public function getAvailableContexts() {
    $context = new Context(new ContextDefinition('entity:group', $this->t("Current user's exchange")));
    return ['group' => $context];
  }

}
