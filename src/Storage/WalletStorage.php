<?php

namespace Drupal\mcapi\Storage;

use Drupal\mcapi\Entity\Wallet;
use Drupal\user\Entity\User;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\Sql\SqlContentEntityStorage;

/**
 * Storage controller for wallet entity.
 */
class WalletStorage extends SqlContentEntityStorage implements WalletStorageInterface {

  /**
   * {@inheritdoc}
   */
  public function getQueryServiceName() {
    return 'mcapi_wallet.query.sql';
  }

  /**
   * {@inheritdoc}
   */
  public static function walletsOf(ContentEntityInterface $entity, $load = FALSE) {
    // There's no elegant static way to get an entityType's entityQuery object
    // or storage
    $wids = \Drupal::entityQuery('mcapi_wallet')
      ->condition('holder_entity_type', $entity->getEntityTypeId())
      ->condition('holder_entity_id', $entity->id())
      ->execute();
    // @todo reduce all this paranoia checking once we are sure its not needed
    if (empty($wids)) {
      if ($entity->isNew()) {
        \Drupal::logger('mcapi')
          ->error('Should not run walletsOf on an unsaved user',  ['exception' => new \Exception()]);
      }
      elseif ($entity->getEntityTypeId() == 'user' && $entity->id() != 1) {
        if ($entity->id()) {
          \Drupal::logger('mcapi')
            ->debug('user '.$entity->id() .' has no wallets', ['exception' => new \Exception()]);
        }
        else {
          //mtrace();
        }
      }
    }
    return $load ?
      Wallet::loadMultiple($wids) :
      $wids;
  }

  /**
   * {@inheritdoc}
   *
   * @todo make this include the entity owners of the holders, but how?
   */
  public static function myWallets($uid) {
    //at the moment this will get the retrieve the same number twice.
    // One of these functions needs to be adjusted
    $account = User::load($uid);
    if (!$account) {
      trigger_error("User $uid does not exist", E_USER_ERROR);
      return [];
    }
    $my_wallets = static::walletsOf($account);
    $burser_of = \Drupal::entityQuery('mcapi_wallet')->condition('bursers', $uid)->execute();
    // But for now, it might be good enough to do array_unique
    return array_unique(array_merge($my_wallets, $burser_of)); // Should be no duplicates
  }

}

