<?php

namespace Drupal\mcapi_exchanges\Overrides;

use Drupal\group\Entity\Group;
use Drupal\group\Access\GroupAccessResult;
use Drupal\user\Entity\User;
use Drupal\Core\Plugin\Context\ContextProviderInterface;
use Drupal\Core\Entity\Query\QueryFactory;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityHandlerInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Currency access per group
 *
 * @see \Drupal\mcapi\Entity\Currency.
 */
class ExchangeCurrencyAccessControlHandler extends EntityAccessControlHandler implements EntityHandlerInterface {

  /**
   * EntityQuery on group entity
   * @var Drupal\Core\Entity\Query\QueryFactory
   */
  protected $queryFactory;

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
  public function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    if ($account->hasPermission('configure mcapi')) {
      return AccessResult::allowed()->cachePerUser();
    }

    switch ($operation) {
      case 'view':
        $result = AccessResult::allowed()->cachePerPermissions();
        break;
      case 'update':
        $gids = $this->queryFactory->get('group')->condition('currencies', $entity->id())->execute();
        if (count($gids) == 1) {
          $group = Group::load(reset($gids));
          $result = GroupAccessResult::allowedIfHasGroupPermission($group, $account, 'manage currencies')
            ->addCacheableDependency($group);
        }
        else {
          // Group members can't update currencies which are in more than one group
          // Or we could add every group as a cachable dependency in case all but one drop the currency.
          $result = AccessResult::forbidden();
        }
        break;

      case 'delete':
        // This is almost never be permissible.
        // There is a problem with count() on the transaction entity Query
        // SELECT 1 AS expression ... GROUP BY base_table.xid
        // @see \Drupal\Core\Database\Query\Select::prepareCountQuery
        $xids = $this->queryFactory->get('mcapi_transaction')
          ->condition('worth.curr_id', $entity->id())
          ->accessCheck(FALSE)
          ->execute();
        if ($xids) {
          $result = AccessResult::forbidden();
        }
        else {
          $result = AccessResult::AllowedIf($entity->access('update'));
        }
    }
    return $result->cachePerUser();
  }

  function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return AccessResult::allowedIfHasPermission($account, 'configure mcapi')->cachePerUser();
  }


}
