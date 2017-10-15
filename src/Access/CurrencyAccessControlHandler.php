<?php

namespace Drupal\mcapi\Access;

use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityHandlerInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\Query\QueryFactory;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines an access controller for the Currency entity.
 *
 * @see \Drupal\mcapi\Entity\Currency.
 */
class CurrencyAccessControlHandler extends EntityAccessControlHandler implements EntityHandlerInterface {

  protected $transactionQuery;

  /**
   * @param EntityTypeInterface $entity_type
   * @param type $transaction_query
   */
  public function __construct(EntityTypeInterface $entity_type, QueryFactory $query_factory) {
    parent::__construct($entity_type);
    $this->transactionQuery = $query_factory->get('mcapi_transaction');
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
    $this->prepareUser($account);
    switch ($operation) {
      case 'view':
        return AccessResult::allowed()->cachePerPermissions();

      case 'create':
      case 'update':
        if ($account->hasPermission('configure mcapi')) {
          return AccessResult::allowed()->cachePerPermissions();
        }
        // i.e it is already saved.
        elseif ($entity->id()) {
          if ($account->id() == $entity->getOwnerId()) {
            $result = AccessResult::allowed()->cachePerUser();
          }
          else {
            $result = AccessResult::forbidden("Cannot update somebody else's wallet")->cachePerUser();
          }
        }
        else {
          $result = AccessResult::forbidden()->cachePerUser();
        }
        break;

      case 'delete':
        // @todo inject service entity.query.config
        $count = $this->transactionQuery
          ->condition('worth.curr_id', $entity->id())
          ->count()
          ->execute();
        $result = $count ?
          AccessResult::forbidden() :
          AccessResult::allowed();
    };
    return $result;
  }

}
