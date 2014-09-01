<?php

/**
 * @file
 * Contains \Drupal\mcapi\Storage\WalletStorage.
 */

namespace Drupal\mcapi\Storage;

use Drupal\Core\Entity\ContentEntityDatabaseStorage;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\mcapi\Entity\WalletInterface;
use Drupal\mcapi\Entity\Wallet;
use Drupal\Core\Entity\EntityInterface;
use Drupal\mcapi\Entity\Exchange;

/**
 * This is an unfieldable content entity. But if we extend the EntityDatabaseStorage
 * instead of the ContentEntityDatabaseStorage then the $values passed to the create
 * method work very differently, putting NULL in all database fields
 */
class WalletStorage extends ContentEntityDatabaseStorage implements WalletStorageInterface {

  /**
   * {@inheritdoc}
   * add the access setting to each wallet
   * @see Drupal\user\UserStorage::mapFromStorageRecords
   */
  function mapFromStorageRecords(array $records) {
    //if (!$records) return array();
    //add the access settings to each wallet
    $q = db_select('mcapi_wallets_access', 'a')
      ->fields('a', array('wid', 'operation', 'uid'))
      ->condition('wid', array_keys($records));

    foreach(Wallet::ops() as $op) {
      foreach ($records as $key => $record) {
        //the zero values will be replaced by an array of user ids from the access table.
        //if all goes according to plan...
        $accesses[$key][$op] = $record->{$op} ? : array();
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
   * @see \Drupal\mcapi\Storage\WalletStorageInterface::getOwnedWalletIds()
   * @todo probably useful to have a flag for excluding _intertrading wallets
   */
  static function getOwnedWalletIds(ContentEntityInterface $entity, $intertrading = FALSE) {
    $q = db_select('mcapi_wallet', 'w')
      ->fields('w', array('wid'))
      ->condition('entity_type', $entity->getEntityTypeId())
      ->condition('pid', $entity->id())
      ->condition('orphaned', 0);
    if (!$intertrading) {
      $q->condition('w.name', '_intertrading', '<>');
    }
    return $q->execute()->fetchCol();
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
   */
  static function filter(array $conditions, $offset = 0, $limit = NULL, $intertrading = FALSE) {
    $query = db_select('mcapi_wallet', 'w')->fields('w', array('wid'));
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
      $conditions['wids'] = mcapi_wallets_in_exchanges($conditions['exchanges']);
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
      $field_names = Exchange::getEntityFieldnames();
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
        $ex_alias = "mcapi_exchange_".$entity_type_id;
        //NB We are assuming the default entity Storage for the Exchange, which is pretty safe
        $query->leftjoin('mcapi_exchange', $ex_alias, "$ref_alias.{$field_name}_target_id = {$ex_alias}.id");
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
   * write the wallet's access settings and the index table
   * @todo this must also run when an entity joins an exchange
   */
  function doSave($wid, EntityInterface $wallet) {
    parent::doSave($wid, $wallet);
    $wid = $wallet->id();//in case it was new
    $this->dropIndex(array($wid));

    //update the access settings
    //I couldn't get merge to work with multiple rows, so removed the table key and doing it manually
    $q = db_insert('mcapi_wallets_access')->fields(array('wid', 'permission', 'value'));
    foreach ($wallet->ops() as $op) {
      if (is_array($wallet->{$op})) {
        foreach ($wallet->$op as $value) {
          $value = array(
            'wid' => $wallet->wid->value,
            'permission' => $op,
            'value' => $value
          );
          $q->values();
        }
      }
    }
    if (isset($value))$q->execute();
  }

  /**
   * {@inheritdoc}
   * why isn't this static?
   */
  function doDelete($entities) {
    parent::doDelete($entities);
    $this->dropIndex(array_keys($entities));
  }

  private function dropindex($wids) {
    db_delete('mcapi_wallets_access')->condition('wid', $wids);
  }

  function getSchema() {
    $schema = parent::getSchema();
    $schema['mcapi_wallet'] += array(
      'foreign keys' => array(
        'mcapi_transactions_payee' => array(
          'table' => 'mcapi_transaction',
          'columns' => array('wid' => 'payee'),
        ),
        'mcapi_transactions_payer' => array(
          'table' => 'mcapi_transaction',
          'columns' => array('wid' => 'payer'),
        ),
      ),
    );
    $schema['mcapi_wallets_access'] = array(
      'description' => "Access settings for wallet's operations",
      'fields' => array(
        'wid' => array(
          'description' => 'the unique wallet ID',
          'type' => 'int',
          'size' => 'normal',
          'not null' => TRUE,
        ),
        'operation' => array(
          'description' => 'One of list, summary, payin, payout, edit',
          'type' => 'varchar',
          'length' => '8',
        ),
        'uid' => array(
          'description' => 'A permitted user id',
          'type' => 'int',
          'length' => '8',
        )
      ),
      'unique keys' => array(
        'walletUserPerm' => array('wid', 'operation', 'uid'),
      ),
      //this table has no keys
    );
    return $schema;
  }

}

