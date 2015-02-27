<?php

/**
 * @file
 * Contains \Drupal\mcapi\Storage\WalletStorage.
 */

namespace Drupal\mcapi\Storage;

use Drupal\Core\Entity\Sql\SqlContentEntityStorage;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\mcapi\WalletInterface;
use Drupal\mcapi\Entity\Wallet;
use Drupal\mcapi\Mcapi;
use Drupal\Core\Entity\EntityInterface;

class WalletStorage extends SqlContentEntityStorage implements WalletStorageInterface {

  /**
   * {@inheritdoc}
   * add the access setting to each wallet
   * @see Drupal\user\UserStorage::mapFromStorageRecords
   */
  function mapFromStorageRecords(array $records) {
    //add the access settings to each wallet
    $q = $this->database->select('mcapi_wallets_access', 'a')
      ->fields('a', array('wid', 'operation', 'uid'))
      ->condition('wid', array_keys($records));

    foreach(Wallet::ops() as $op) {
      foreach ($records as $key => $record) {
        //the zero values will be replaced by an array of user ids from the access table.
        //if all goes according to plan...
        $accesses[$key][$op] = $record->{$op} ? : [];
      }
    }
    $entities = parent::mapFromStorageRecords($records);
    //now populate the arrays where specific users have been specified
    foreach ($accesses as $key => $ops) {
      $entities[$key]->access = $ops;
    }

    foreach ($q->execute() as $row) {
      $entities[$row->wid]->access[$row->operation][] = $row->uid;
    }
    return $entities;
  }

  /**
   * @see \Drupal\mcapi\Storage\WalletStorageInterface::getOwnedIds()
   */
  static function getOwnedIds(ContentEntityInterface $entity, $intertrading = FALSE) {
    //This is functionality equivalent to, but faster than entity_load_multiple_by_properties()
    $q = db_select('mcapi_wallet', 'w')
      ->fields('w', array('wid'))
      ->condition('entity_type', $entity->getEntityTypeId())
      ->condition('pid', $entity->id())
      ->condition('orphaned', 0);
    if (!$intertrading) {
      $q->condition('w.name', '_intertrading', '<>');
    }
    return $q->execute()->fetchCol();
  }


  /**
   * (non-PHPdoc)
   * @see \Drupal\mcapi\Storage\WalletStorageInterface::filter()
   */
  static function filter(array $conditions, $offset = 0, $limit = NULL, $intertrading = FALSE) {
    $query = db_select('mcapi_wallet', 'w')->fields('w', array('wid'));
    $namelike = db_or();
    $like = FALSE;

    if (\Drupal::moduleHandler()->moduleExists('mcapi_exchange')) {
      //try to move this to the exchanges module by altering something
      watchdog('wallet', 'add filtering by exchange to the wallet');
    }
    if (array_key_exists('exchanges', $conditions)) {die('exchanges key in wallet filter is deprecated');
      //get all the wallets in all the exchanges mentioned
      //this is easier than trying to join with all the wallet owner base entity tables
      //static function means we have to call up this object again
      $conditions['wids'] = Self::inExchanges($conditions['exchanges']);
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

    if (!empty($conditions['fragment'])) {
      $string = '%'.db_like($conditions['fragment']).'%';
      $namelike->condition('w.name', $string, 'LIKE');
      $like = TRUE;
      //remember that it is only possible to match against owner names
      //if each of the owner types can have no more than one wallet.
      //which entitytypes are we considering? if none were passed, then all of them
      if (empty($conditions['entity_types'])) {
        $conditions['entity_types'] = array_keys(Mcapi::walletableBundles());
      }
      //we need to identify the base table and 'name' field for each entity type
      //we are searching against. Hopefully this will work for all well-formed 
      //entityTypes!
      foreach ($conditions['entity_types'] as $entity_type_id) {
        //might be better practice to get the EntityType object from the entity than the Definition from the entityManager
        $entity_info = \Drupal::entityManager()->getDefinition($entity_type_id, TRUE);
        //we need to make a different alias for every entity type we join to
        $alias = $entity_type_id;
        $entity_table = $entity_info->getDataTable() ? : $entity_info->getBaseTable();

        $query->leftjoin($entity_table, $alias, "w.pid = $alias.uid");
        if ($entity_type_id == 'user') {
          //\Drupal\user\UserAutocomplete the the query checks against the entity table 'name' field.
          //so we'll do the same here, even though 'name' isn't the official label key for the user entity
          $namelike->condition($alias.'.name', $string, 'LIKE');
        }
        elseif ($label_key = $entity_info->getKey('label')) {
          //or use entityType->getKey('label')
          $namelike->condition($alias.'.'.$label_key, $string, 'LIKE');
        }
        debug('Need to rewrite this query with  EXCHANGE_OG_REF');
        /*
        //We are joining both to the entity table and to its exchanges reference
        //and to the exchanges table itself to check the exchange is enabled.
        $ref_table = $entity_type_id.'__'. EXCHANGE_OG_REF;//the entity reference field table name
        $ref_alias = "x{$alias}";//an alias for the entity reference field table
        $join_clause = "$ref_alias.entity_id = $alias.". $entity_info->getKey('id');
        $query->leftjoin($ref_table, $ref_alias, $join_clause);
        //and ANOTHER join to ensure that the referenced exchange is enabled.
        $ex_alias = "mcapi_exchange_".$entity_type_id;
        //NB We are assuming the default entity Storage for the Exchange, which is pretty safe
        $query->leftjoin('mcapi_exchange', $ex_alias, "$ref_alias.".EXCHANGE_OG_REF."_target_id = {$ex_alias}.id");
        $query->condition("$ex_alias.status", 1);
        */
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
   * write the wallet's access settings and the index table
   * @todo this must also run when an entity joins an exchange
   */
  function doSave($wid, EntityInterface $wallet) {
    parent::doSave($wid, $wallet);
    $this->reIndex(array($wallet->id() => $wallet));
  }

  /**
   * {@inheritdoc}
   * why isn't this static?
   */
  function doDelete($entities) {
    parent::doDelete($entities);
    $this->dropIndex($entities);
  }
  
  /**
   * 
   * @param array \Drupal\mcapi\Entity\Wallet[]
   *   keyed by wallet id
   */
  public function reIndex(array $wallets) {
    $this->dropIndex($wallets);
    $query = $this->database->insert('mcapi_wallets_access')
      ->fields(array('wid', 'permission', 'value'));
    
    foreach ($wallets as $wid => $wallet) {
      foreach (Wallet::ops() as $op) {
        if (is_array($wallet->{$op})) {
          foreach ($wallet->$op as $value) {
            $values = array(
              'wid' => $wid(),
              'permission' => $op,
              'value' => $value
            );
            debug($values);
            $access_query->values($values);
          }
        }
      }
    }
    if (isset($values)) {
      $query->execute();
    }
  }
  
  /**
   * 
   * @param array $wids
   */
  private function dropIndex(array $wallets) {
    if ($wids = array_keys($wallets)) {
      $this->database->delete('mcapi_wallets_access')
        ->condition('wid', $wids)
        ->execute();
    }
  }
}

