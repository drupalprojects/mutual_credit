<?php

namespace Drupal\mcapi_exchanges\Overrides;

use Drupal\group\Entity\Access\GroupAccessControlHandler;
use Drupal\group\Access\GroupAccessResult;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\Query\QueryFactory;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityHandlerInterface;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Access controller for the Exchange type of group entity.
 */
class ExchangeAccessControlHandler extends GroupAccessControlHandler implements EntityHandlerInterface {

  /**
   * @param \Drupal\mcapi_exchanges\Overrides\EntityTypeInterface $entity_type
   * @param Drupal\Core\Entity\Query\QueryFactory $query_factory
   */
  public function __construct(EntityTypeInterface $entity_type, QueryFactory $query_factory) {
    parent::__construct($entity_type);
    $this->queryFactory = $query_factory;
  }

  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    return new static(
      $entity_type,
      $container->get('entity.query')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    if ($entity->bundle() == 'exchange') {
      switch ($operation) {
        case 'view':
          $result = GroupAccessResult::allowedIfHasGroupPermission($entity, $account, 'view group');
          break;
        case 'update':
          $result = GroupAccessResult::allowedIfHasGroupPermission($entity, $account, 'edit group');
          break;
        case 'delete':
          // An exchange with transactions or users simply cannot be deleted.
          $transactions = $this->queryFactory->get('group_content')
            ->condition('type', 'exchange-transaction')
            ->condition('gid', $entity->id())
            ->count()->execute();
          $users = $this->queryFactory->get('group_content')
            ->condition('type', 'exchange-group_membership')
            ->condition('gid', $entity->id())
            ->condition('entity_id', '1', '<>')
            ->count()->execute();
          $result = AccessResult::AllowedIf(empty($transactions) && empty($users));
          break;
        default:
          $result = AccessResult::neutral();
      }
      return $result->cachePerPermissions();
    }
    return parent::checkAccess($entity, $operation, $account);
  }

}
