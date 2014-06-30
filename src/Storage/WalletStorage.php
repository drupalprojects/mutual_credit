<?php

/**
 * @file
 * Contains \Drupal\mcapi\Storage\WalletStorage.
 */

namespace Drupal\mcapi\Storage;

use Drupal\Core\Entity\ContentEntityDatabaseStorage;
use Drupal\Core\Entity\ContentEntityInterface;
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
  static function getOwnedWalletIds(ContentEntityInterface $entity) {
    return db_select('mcapi_wallets', 'w')
      ->fields('w', array('wid'))
      ->condition('entity_type', $entity->getEntityTypeId())
      ->condition('pid', $entity->id())
      ->condition('orphaned', 0)
      ->execute()->fetchCol();
    /*N.B. the above is functionality equivalent to, but faster than
    return entity_load_multiple_by_properties('mcapi_wallet',
      array(
        'pid' => $entity->id(),
        'entity_type' => $entity->getEntityTypeId(),
        'orphaned' => FALSE
      )
    );*/
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
  static function filter(array $conditions, $offset = 0, $limit = NULL, $intertrading = FALSE) {

    $query = db_select('mcapi_wallets', 'w')->fields('w', array('wid'));
    $namelike = db_or();
    $like = FALSE;

    if (array_key_exists('fragment', $conditions)) {
      $string = '%'.db_like($conditions['fragment']).'%';
      $namelike->condition('w.name', $string, 'LIKE');
      $like = TRUE;
    }
    if (array_key_exists('exchanges', $conditions)) {
      //get all the wallets in all the exchanges mentioned
      //this is easier than trying to join with all the wallet owner base entity tables
      //static function means we have to call up this object again
      $conditions['wids'] = \Drupal::EntityManager()
        ->getStorage('mcapi_wallet')
        ->walletsInExchanges($conditions['exchanges']);
    }
    if (array_key_exists('wids', $conditions)) {
      $query->condition('w.wid', $conditions['wids']);
    }
    if (array_key_exists('wid', $conditions)) {
      $query->condition('w.wid', $conditions['wid']);
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
    }

    if (isset($string)) {//this is an autocomplete string filter
      //remember that it is only possible to match against owner names
      //if each of the owner types can have no more than one wallet.
      $entity_wallets = \Drupal::config('mcapi.wallets')->get('entity_types');
      $nocando = FALSE;
      //which entitytypes are we considering? if none were passed, then all of them
      if (empty($conditions['entity_types'])) {
        foreach(array_keys($entity_wallets) as $entity_type_bundle) {
          $conditions['entity_types'][] = substr($entity_type_bundle, 0, strpos($entity_type_bundle, ':'));
        }
      }
      //we need to identify the base table and 'name' field for each entity type we are searching against
      $field_names = get_exchange_entity_fieldnames();
      foreach ($conditions['entity_types'] as $entity_type_id) {
        $field_name = $field_names[$entity_type_id];
        //might be better practice to get the EntityType object from the entity than the Definition from the entityManager
        $entity_info = \Drupal::entityManager()->getDefinition($entity_type_id, TRUE);
        //we need to make a different alias for every entity type we join to
        $alias = $entity_type_id;
        $entity_table = $entity_info->getDataTable() ? : $entity_info->getBaseTable();

        $query->leftjoin($entity_table, $alias, "w.pid = $alias.uid");
        if ($entity_type_id == 'user') {
          //\Drupal\user\UserAutocomplete the the query checks against the entity table 'name' field.
          //so we'll do the same here, even though 'name' isn't the official label key for the user entity
          $namelike->condition('user.name', $string, 'LIKE');
        }
        elseif ($label_key = $entity_info->getKey('label')) {
          //or use entityType->getKey('label')
          $namelike->condition($alias.'.'.$label_key, $string, 'LIKE');
        }
        //We are joining both to the entity table and to its exchanges reference
        //and to the exchanges table itself to check the exchange is enabled.
        $ref_table = $entity_type_id.'__'.$field_name;//the entity reference field table name
        $ref_alias = "x{$alias}";//an alias for the entity reference field table
        $join_clause = "$ref_alias.entity_id = $alias.". $entity_info->getKey('id');
        $query->leftjoin($ref_table, $ref_alias, $join_clause);
        //and ANOTHER join to ensure that the referenced exchange is enabled.
        $ex_alias = "mcapi_exchanges_".$entity_type_id;
       // echo $ex_alias;
        $query->leftjoin('mcapi_exchanges', $ex_alias, "$ref_alias.{$field_name}_target_id = {$ex_alias}.id");
        $query->condition("$ex_alias.status", 1);
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
  static function walletsInExchanges(array $exchange_ids) {
    $query = db_select('mcapi_wallet_exchanges_index', 'w')
      ->fields('w', array('wid'));
    if ($exchange_ids) {
      $query->condition('exid', $exchange_ids);
    }
    return $query->execute()->fetchCol();
  }

  /*
   * when an entity joins an exchange this must be updated
   * Does this belong in the WalletStorageInterface?
   */
  function updateIndex(WalletInterface $wallet) {
    $wid = $wallet->id();
    $this->dropIndex(array($wid));
    $query = db_insert('mcapi_wallet_exchanges_index')->fields(array('wid', 'exid'));
    foreach (array_keys(referenced_exchanges($wallet->getOwner())) as $exid) {
      $query->values(array('wid' => $wid, 'exid' => $exid));
    }
    $query->execute();
  }

  /*
   *
   */
  static function dropIndex(array $wids) {
    db_delete('mcapi_wallet_exchanges_index')->condition('wid', $wids)->execute();
  }

}

