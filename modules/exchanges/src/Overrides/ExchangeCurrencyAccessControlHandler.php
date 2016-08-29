<?php

namespace Drupal\mcapi_exchanges\Overrides;

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
   * The exchange group whose currencies we are checking.
   * @var \Drupal\group\Entity\Group
   */
  protected $exchange;

  /**
   *
   * @param \Drupal\mcapi_exchanges\Overrides\EntityTypeInterface $entity_type
   * @param type $context_handler
   */
  public function __construct(EntityTypeInterface $entity_type, $context) {
    parent::__construct($entity_type);
    // I've got really few examples of how to do this.
    $this->exchangeContext = $context->getRuntimeContexts(['exchange'])['exchange']->getContextValue();
  }


  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    return new static(
      $entity_type,
      $container->get('mcapi_exchanges.exchange_context')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {


    switch ($operation) {
      case 'view':
        return AccessResult::allowed()->cachePerPermissions();

      case 'create':
        //only group admins can create and can only do so within that group.
        drupal_set_message('Access control for currency creation not coded yet');
        return AccessResult::forbidden();
        break;
      case 'update':
        // If the currency is in one group only, $account must be admin of that group.
        //
        //
        // Otherwise:
        return AccessResult::allowedIfHasPermission($account, 'configure mcapi')->cachePerPermissions();

        break;

      case 'delete':
        $count = \Drupal::entityQuery('mcapi_transaction')
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
