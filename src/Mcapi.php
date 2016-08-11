<?php

namespace Drupal\mcapi;

use Drupal\mcapi\Entity\Wallet;
use Drupal\system\Entity\Action;
use Drupal\Core\Url;
use Drupal\Core\Link;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Component\Utility\Html;

/**
 * Utility class for community accounting.
 */
class Mcapi {

  /**
   * Get a list of bundles which can have wallets.
   *
   * Which is to say, whether it is a contentEntity configured to have at least
   * 1 wallet.
   *
   * @return array
   *   Bundle names keyed by entity type ids.
   */
  public static function walletableBundles($reset = FALSE) {
    $bundles = &drupal_static(__FUNCTION__);
    if (!is_array($bundles) || $reset) {
      if ($cache = \Drupal::cache()->get('walletableBundles')) {
        $bundles = $cache->data;
      }
      else {
        $bundles = [];
        $types = \Drupal::Config('mcapi.settings')->get('entity_types');
        // Having some problems on installation
        if (!$types) {
          return [];// so we don't cache nothing.
        }
        foreach (\Drupal::Config('mcapi.settings')->get('entity_types') as $entity_bundle => $max) {
          if (!$max) {
            continue;
          }
          list($type, $bundle) = explode(':', $entity_bundle);
          $bundles[$type][$bundle] = $max;
        }
        \Drupal::cache()->set(
          'walletableBundles',
          $bundles,
          CacheBackendInterface::CACHE_PERMANENT,
        // @todo what are the tags?
          ['walletable_bundles']
        );
      }
    }
    return $bundles;
  }

  /**
   * Find the maximum number of wallets a bundle can have.
   *
   * @param string $entity_type_id
   *   The machine name of the entity type.
   * @param string $bundle
   *   The machine name of the bundle.
   *
   * @return int
   *   The maximum number of wallets.
   */
  public static function maxWalletsOfBundle($entity_type_id, $bundle) {
    $bundles = static::walletableBundles();
    if (isset($bundles[$entity_type_id])) {
      if (isset($bundles[$entity_type_id][$bundle])) {
        return $bundles[$entity_type_id][$bundle];
      }
    }
    return 0;
  }

  /**
   * Utility function.
   *
   * Loads any of the transaction operation actions.
   *
   * @param string $operation
   *   May or may not begin with transaction_'.
   *
   * @todo alter the incoming $operation to be more consistent and harminise
   * with $this::transactionActionsLoad().
   */
  public static function transactionActionLoad($operation) {
    // Sometimes the $operation is from the url so it is shortened, and
    // sometimes is the id of an action. There is a convention that all
    // transaction actions take the form transaction_ONEWORD take the oneword
    // from the given path.
    if ($operation == 'operation') {
      $operation = \Drupal::routeMatch()->getParameter('operation');
    }
    if (substr($operation, 0, 12) != 'transaction_') {
      $action_name = 'transaction_' . $operation;
    }
    else {
      $action_name = $operation;
    }
    if ($action = Action::load($action_name)) {
      return $action;
    }
    throw new \Exception("No action with name '$operation'");
  }

  /**
   * Utility function.
   *
   * Load those special transaction operations which are also actions.
   */
  public static function transactionActionsLoad() {
    return \Drupal::entityTypeManager()
      ->getStorage('action')
      ->loadByProperties(['type' => 'mcapi_transaction']);
  }

  /**
   * Uasort callback for configuration entities.
   *
   * Should have been included in Drupal Core?
   */
  public static function uasortWeight($a, $b) {
    $a_weight = (is_object($a) && property_exists($a, 'weight')) ? $a->weight : 0;
    $b_weight = (is_object($b) && property_exists($b, 'weight')) ? $b->weight : 0;
    if ($a_weight == $b_weight) {
      return 0;
    }
    return ($a_weight < $b_weight) ? -1 : 1;
  }

  /**
   * Utility function to populate a form widget's options with entity names.
   *
   * @param string $entity_type_id
   *   The machine name of the entity type.
   * @param array $data
   *   Either entities of the given type, entity ids, or $conditions for
   *   entity_load_multiple_by_properties.
   *
   * @return string[]
   *   The entity names, keyed by entity id.
   *
   * @see Drupal\Core\Entity\Plugin\EntityReferenceSelection::getReferenceableEntities
   */
  public static function entityLabelList($entity_type_id, array $data = []) {
    if (empty($data)) {
      $entities = \Drupal::entityTypeManager()->getStorage($entity_type_id)->loadMultiple();
    }
    elseif (is_string(key($data))) {
      // That means it is a conditions list.
      $entities = entity_load_multiple_by_properties($entity_type_id, $data);
    }
    elseif (is_numeric(reset($data))) {
      $entities = \Drupal::entityTypeManager()->getStorage($entity_type_id)->loadMultiple($data);
    }
    else {
      $entities = $data;
    }
    if (property_exists(current($entities), 'weight') && count($entities) > 1) {
      uasort($entities, '\Drupal\mcapi\Mcapi::uasortWeight');
    }
    $list = [];
    foreach ($entities as $entity) {
      $list[$entity->id()] = Html::escape(\Drupal::entityManager()->getTranslationFromContext($entity)->label());
    }
    return $list;
  }


  /**
   * Retrieve all the wallets held by a given ContentEntity.
   *
   * @param EntityInterface $entity
   *   The entity.
   * @param bool $load
   *   TRUE means return the fully loaded wallets.
   *
   * @return \Drupal\mcapi\Entity\WalletInterface[]
   *   Or just the wallet ids if $load is FALSE.
   *
   * @todo change this to take the entityTypeId and the EntityId instead of the Entity
   */
  public static function walletsOf(EntityInterface $entity, $load = FALSE) {
    $wids = \Drupal::entityQuery('mcapi_wallet')
      ->condition('holder_entity_type', $entity->getEntityTypeId())
      ->condition('holder_entity_id', $entity->id())
      ->condition('payways', Wallet::PAYWAY_AUTO, '<>')
      ->condition('orphaned', 0)
      ->execute();
    return $load ?
      \Drupal::entityTypeManager()->getStorage('mcapi_wallet')->loadMultiple($wids) :
      $wids;
  }

  /**
   * Retrieve all the wallets held by any of many ContentEntities.
   *
   * @param ContentEntityInterface[] $entities
   *   An array of content entities.
   *
   * @return int[]
   *   The wallet IDs.
   */
  public static function walletsOfHolders(array $entities) {
    $query = \Drupal::database()->select('mcapi_wallet', 'w')
      ->fields('w', ['wid'])
      ->condition('orphaned', 0)
      ->condition('payways', Wallet::PAYWAY_AUTO, '<>');

    $or = $query->orConditionGroup();
    foreach ($entities as $entity_type_id => $entity_ids) {
      $and = $query->andConditionGroup()
        ->condition('holder_entity_type', $entity_type_id)
        ->condition('holder_entity_id', $entity_ids, 'IN');
      $or->condition($and);
    }
    return $query->condition($or)->execute()->fetchCol();
  }

  /**
   * Whether a user can payin to at least one and payout of at least one wallet.
   *
   * @param int $uid
   *   The user ID.
   *
   * @return bool
   *   TRUE if the $uid has access to enough wallets to trade.
   */
  public static function enoughWallets($uid) {
    $walletStorage = \Drupal::entityTypeManager()->getStorage('mcapi_wallet');
    $payin = $walletStorage->whichWalletsQuery('payin', $uid);
    $payout = $walletStorage->whichWalletsQuery('payout', $uid);
    if (count($payin) < 2 && count($payout) < 2) {
      // This is deliberately ambiguous as to whether you have no wallets or the
      // system has no other wallets.
      drupal_set_message(t('There are no wallets for you to trade with'), 'warning');
      return FALSE;
    }
    // There must be at least one wallet in each (and they must be different!)
    return (count(array_unique(array_merge($payin, $payout))) > 1);
  }

  /**
   * Service container.
   *
   * @return \Drupal\mcapi\Plugin\TransactionRelativeManager
   *   The 'Transaction relative manager' service.
   */
  public static function transactionRelatives($plugin_names = []) {
    return \Drupal::service('mcapi.transaction_relative_manager')->activatePlugins($plugin_names);
  }

  /**
   * Get a list of all the tokens on the transaction entity.
   *
   * @note This really needs replacing with a core function.
   */
  public static function tokenHelp() {
    foreach (array_keys(\Drupal::Token()->getInfo()['tokens']['xaction']) as $token) {
      $tokens[] = "[xaction:$token]";
    }
    return implode(', ', $tokens);
  }

  /**
   * Get a string describing where to get help writing Twig.
   */
  public static function twigHelp() {
    foreach (array_keys(\Drupal::Token()->getInfo()['tokens']['xaction']) as $token) {
      $tokens[] = '{{ ' . $token . '}}';
    }
    // @todo how to place links in $element['#description']?
    $link = Link::fromTextAndUrl(
        t('What is twig?'),
        Url::fromUri('http://twig.sensiolabs.org/doc/templates.html')
      );
    return implode(', ', $tokens) . '. ' . $link->toString();

  }

  /**
   * Get the wallet IDs corresponding to a search string and directionality.
   *
   * @param string $match
   *   A string to be matched against the wallet name.
   * @param string $restrict
   *   One of 'payin', 'payout' or empty.
   *
   * @return integer[]
   *   A list of wallet ids.
   */
  public static function getWalletSelection($match = '', $restrict = '') {
    $walletStorage = \Drupal::entityTypeManager()->getStorage('mcapi_wallet');
    if ($restrict) {
      $wids = $walletStorage->whichWalletsQuery($restrict, \Drupal::currentUser()->id(), $match);
    }
    else {
      $query = \Drupal::entityQuery('mcapi_wallet')
      // Not intertrading wallets.
        ->condition('payways', Wallet::PAYWAY_AUTO, '<>')
        ->condition('orphaned', 0);
      if ($match) {
        $query->condition('name', '%' . \Drupal::database()->escapeLike($match) . '%', 'LIKE');
      }
      $wids = $query->execute();
    }

    return $wids;
  }

  /**
   * Get the first or main wallet held by an entity.
   *
   * @param ContentEntityInterface $entity
   *   Any content entity.
   */
  public static function firstWalletIdOfEntity(ContentEntityInterface $entity) {
    $wids = Mcapi::walletsOf($entity);
    return reset($wids);
  }

}
