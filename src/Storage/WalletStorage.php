<?php

/**
 * @file
 * Contains \Drupal\mcapi\Storage\WalletStorage.
 */

namespace Drupal\mcapi\Storage;

use Drupal\Core\Entity\ContentEntityDatabaseStorage;
use Drupal\Core\Entity\ContentEntityInterface;//TODO make a wallet interface
use Drupal\mcapi\Entity\WalletInterface;

/**
 * This is an unfieldable content entity. But if we extend the EntityDatabaseStorage
 * instead of the ContentEntityDatabaseStorage then the $values passed to the create
 * method work very differently, putting NULL in all database fields
 */
class WalletStorage extends ContentEntityDatabaseStorage implements WalletStorageInterface {
  /**
   * @see \Drupal\mcapi\Storage\WalletStorageInterface::getOwnedWalletIds()
   * @todo probably useful to have a flag for excluding _intertrading wallets
   */
  function getOwnedWalletIds(ContentEntityInterface $entity) {
    return db_select('mcapi_wallets', 'w')
      ->fields('w', array('wid'))
      ->condition('entity_type', $entity->getEntityTypeId())
      ->condition('pid', $entity->id())
      ->condition('orphaned', 0)
      ->execute()->fetchCol();
  }

  /**
   * @see \Drupal\mcapi\Storage\WalletStorageInterface::spare()
   */
  function spare(ContentEntityInterface $owner) {
    //check the number of wallets already owned against the max for this entity type
    $wids = $this->getOwnedWalletIds($owner);
    $bundle = $owner->getEntityTypeId().':'.$owner->bundle();
    $max = \Drupal::config('mcapi.wallets')->get('entity_types.'.$bundle);
    if (count($wids) < $max) return TRUE;
    return FALSE;
  }
  /**
   * (non-PHPdoc)
   * @see \Drupal\mcapi\Storage\WalletStorageInterface::filter()
   *
   */
  function filter(array $conditions, $offset = 0, $limit = NULL, $intertrading = FALSE) {

    $query = db_select('mcapi_wallets', 'w')->fields('w', array('wid'));
    $namelike = db_or();
    $like = FALSE;

    if (array_key_exists('fragment', $conditions)) {
      $string = '%'.db_like($conditions['fragment']).'%';
      $namelike->condition('w.name', $string, 'LIKE');
      $like = TRUE;
    }

    if (array_key_exists('exchanges', $conditions)) {
      //get all the wallets in all the exchanges mentioned, and then just filter on them
      //this means we don't have to attempt to join to all the exchange fieldAPI tables
      $conditions['wids'] = $this->walletsInExchanges($conditions['exchanges']);
    }

    if (array_key_exists('wids', $conditions)) {
      $query->condition('w.wid', $conditions['wids']);
    }

    if (array_key_exists('orphaned', $conditions)) {
      $query->condition('w.orphaned', $conditions['orphaned']);
    }

    if (array_key_exists('owner', $conditions)) {
      $query->condition('entity_type', $conditions['owner']->getEntityTypeId())
      ->condition('pid', $conditions['owner']->id());
    }

    if (array_key_exists('entity_types', $conditions)) {
      $query->condition('w.entity_type', $conditions['entity_types']);

      if ($string) {
        //remember that it is only possible to match against owner names
        //if each of the owner types can have no more than one wallet.
        //TODO inject the wallet config to $this->$entity_wallets so we
        //don't have to call the global scope here
        $types = $conditions['entity_types'];
        $entity_wallets = \Drupal::config('mcapi.wallets')->get('entity_types');
        $nocando = FALSE;
        foreach ($types as $type) {
          if ($entity_wallets[$type] == 1) continue;
          $nocando = TRUE; break;
        }
        //
        if (!$nocando) {
          //we need to identify the base table and 'name' field for each entity type we are searching against
          $fieldnames = get_exchange_entity_fieldnames();
          foreach ($types as $entity_type_id) {
            //might be better practice to get the EntityType object than the Definition
            $entity_info = \Drupal::entityManager()->getDefinition($entity_type_id, TRUE);
            //we need to make a different alias for every entity type we join to
            $alias = $entity_type_id;
            $entity_table = @$entity_info['data_table'] ? : $entity_info['base_table'];
            $query->leftjoin($entity_table, $alias, "w.pid = $alias.uid");
            if (array_key_exists('label', $entity_info['entity_keys'])) {
              //or use entityType->getKey('label')
              $namelike->condition($alias.'.'.$entity_info['entity_keys']['label'], $string, 'LIKE');
            }
            //We are joining both to the entity table and to its exchanges reference
            //and to the exchanges table itself to check the exchange is enabled.
            //or use entityType->getKey('id')
            $key = $entity_info['entity_keys']['id'];//id is a required key
            $ref_table = $entity_type.'__'.$fieldname;//the entity reference field table name
            $ref_alias = "x{$alias}";//an alias for the entity reference field table
            $query->leftjoin($ref_table, $ref_alias, "$ref_alias.entity_id = {$alias}.{$key}");
            //and ANOTHER join to ensure that the referenced exchange is enabled.
            $ex_alias = "mcapi_exchanges_".$entity_type;
            $query->leftjoin('mcapi_exchanges', $ex_alias, "$ref_alias.{$fieldname}_target_id = mcapi_exchanges.id");
            $query->condition("$ex_alias.active", 1);

          }
        }

      }
    }
    if (!$intertrading) {
      $query->condition('w.name', '_intertrading', '<>');
    }
    //we know that user is is one of the entities in this query
    if ($like) {
      $query->condition($namelike);
    }
    if ($limit) {
      //passing $limit = NULL gets converted to limit = 0, which is bad
      //however it is safe to say that if there is no limit there is no offset, right?
      $query->range($offset, $limit);
    }
    return $query->execute()->fetchcol();
  }

  /**
   * Get all the wallet ids in given exchanges.
   * this can also be done with filter() but is quicker
   * maybe not worth it if this is only used once, in any case the index table is needed for views
   * Each wallet owner has a required entity reference field pointing to exchanges
   * @todo put this in the interface
   *
   * @param array $exchange_ids
   * @return array
   *   the non-orphaned wallet ids from the given exchanges
   */
  function walletsInExchanges($exchange_ids) {
    $query = db_select('mcapi_wallet_exchanges_index', 'w')
      ->fields('w', array('wid'));
    if ($exchange_ids) {
      $query->condition('exid', $exchange_ids);
    }
    return $query->execute()->fetchCol();
  }

  //when user joins an exchange this must be updated
  function updateIndex(WalletInterface $wallet) {
    $this->dropIndex(array($wallet));
    $query = db_insert('mcapi_wallet_exchanges_index')->fields(array('wid', 'exid'));
    foreach (array_keys(referenced_exchanges($wallet->getOwner())) as $id) {
      $query->values(array('wid' => $wallet->id(), 'exid' => $id));
    }
    $query->execute();
  }

  //TODO when a user leaves an exchange, this must be updated
  function dropIndex(array $wallets) {
    foreach ($wallets as $wallet) {
      $wids[] = $wallet->id();
    }
    db_delete('mcapi_wallet_exchanges_index')->condition('wid', $wids)->execute();
  }

}

