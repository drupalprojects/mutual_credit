<?php

namespace Drupal\mcapi\Access;

/**
 * @file
 * Contains \Drupal\mcapi\Access\TransactionAccessControlHandler.
 */

use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\HttpFoundation\Request;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Access\AccessResult;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines an access controller option for the mcapi_transaction entity.
 */
class TransactionAccessControlHandler extends EntityAccessControlHandler {
  
  private $routeMatch;
  private $transitionManager;

  public function __construct( $entity_type) {
    //I don't know how to inject when create() is not called
    $this->routeMatch = \Drupal::RouteMatch();
    $this->transitionManager = \Drupal::Service('mcapi.transitions');
  }
  
  /**
   * {@inheritdoc}
   */
  public function access(EntityInterface $transaction, $transition, $langcode = LanguageInterface::LANGCODE_DEFAULT, AccountInterface $account = NULL, $return_as_object = false) {
    if ($transition == 'transition') {//wtf?
      $transition = $this->routeMatch->getParameter('transition');
    }
    //the decision is taken by the plugin for the given transition operation
    if ($plugin = $this->transitionManager->getPlugin($transition)) {
      if ($plugin->opAccess($transaction, $this->prepareUser($account))) {
        return AccessResult::allowed()->cachePerUser();
      }
    }
    return AccessResult::forbidden()->cachePerUser();
  }
  
}
