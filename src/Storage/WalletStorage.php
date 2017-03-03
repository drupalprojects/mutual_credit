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
   */
  public function getQueryServiceName() {
    return 'mcapi_wallet.query.sql';
  }

  /**
   * Get the wallets a user controls, which means holds, is burser of, or is the
   * entityOwner of the holder.
   *
   * @param int $uid
   *
   * @return int[]
   *   The wallet ids.
   *
   * @todo make this include the entity owners of the holders, but how?
   */
  public function myWallets($uid) {
    $wids = array_merge(
      static::walletsOf(User::load($uid)),
      $this->getQuery()->condition('bursers', $uid)->execute()
    );
    return $wids;
  }

}

