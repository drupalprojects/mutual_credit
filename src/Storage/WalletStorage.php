<?php

/**
 * @file
 * Contains \Drupal\mcapi\Storage\WalletStorage.
 */

namespace Drupal\mcapi\Storage;

use Drupal\Core\Entity\Sql\SqlContentEntityStorage;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\mcapi\WalletInterface;
use Drupal\mcapi\Exchange;
use Drupal\Core\Entity\EntityInterface;

class WalletStorage extends SqlContentEntityStorage {

  /**
   * {@inheritdoc}
   * add the access setting to each wallet
   */
  function mapFromStorageRecords(array $records, $load_from_revision = false) {
    $entities = parent::mapFromStorageRecords($records);
    //populate the access property with the single char values
    foreach(array_keys(Exchange::walletOps()) as $op) {
      foreach ($records as $key => $record) {
        if (!in_array($record->{$op}, [WALLET_ACCESS_OWNER, WALLET_ACCESS_USERS])) {
          $entities[$key]->access[$op] = $record->{$op};
        }
      }
    }
    //populate the access property with the specific user values
    $q = $this->database->select('mcapi_wallets_access', 'a')
      ->fields('a', array('wid', 'operation', 'uid'))
      ->condition('wid', array_keys($records), 'IN');
    foreach ($q->execute() as $row) {
      $entities[$row->wid]->access[$row->operation][] = $row->uid;
    }
    //hopefully by now there is a string or array for each of the 4 access operations
    return $entities;
  }

  /**
   * {@inheritdoc}
   * write the wallet's access settings and the wallet holder index table
   */
  function doSave($wid, EntityInterface $wallet) {
    parent::doSave($wid, $wallet);
    $this->reIndex(array($wallet->id() => $wallet));
  }

  /**
   * {@inheritdoc}
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
      ->fields(array('wid', 'operation', 'uid'));
    foreach ($wallets as $wid => $wallet) {
      foreach (array_keys(Exchange::walletOps()) as $op) {
        if (isset($wallet->access[$op]) && is_array($wallet->access[$op])) {
          foreach ($wallet->access[$op] as $value) {
            $values = [$wid, $op, $value];
            $query->values($values);
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
        ->condition('wid', $wids, 'IN')
        ->execute();
    }
  }

  /**
   * {@inheritdoc}
   */
  function walletsUserCanActOn($operation, $account) {
    $wallets = [];
    $or = db_or();
    //wallets the user owns
    $or->condition(db_and()
      ->condition('entity_type', 'user')
      ->condition('pid', $account->id()));
    //or wallets which can be acted on by all, including anon
    $or->condition($operation, 1);
    //wallets which can be acted on by authenticated users
    if ($account->id()) {
      $or->condition($operation, 2);
    }
    //query to get all the wallets this user can act on
    $w1 = $this->database->select('mcapi_wallet', 'w')
      ->fields('w', ['wid'])
      ->condition($or)
      ->execute();
    //query to get all the wallets this user can act on as a named user.
    $w2 = $this->database->select('mcapi_wallets_access', 'w')
      ->fields('w', ['wid'])
      ->condition('operation', $operation)
      ->condition('uid', $account->id())
      ->execute();
    return array_unique(array_merge($w1->fetchCol(), $w2->fetchCol()));
  }

  /**
   * {@inheritdoc}
   */
  function filter(array $conditions, $offset = 0, $limit = NULL) {
    $query = db_select('mcapi_wallet', 'w')->fields('w', array('wid'));
    $namelike = db_or();
    $like = FALSE;

    if (array_key_exists('wids', $conditions)) {
      $query->condition('w.wid', $conditions['wids']);
    }
    if (array_key_exists('wid', $conditions)) {
      $query->condition('w.wid', $conditions['wid']);
    }

    if (array_key_exists('orphaned', $conditions)) {
      $query->condition('w.orphaned', $conditions['orphaned']);
    }
    if (array_key_exists('intertrading', $conditions)) {
      $operator = $conditions['intertrading'] ? '=' : '<>';
      $query->condition('w.name', '_intertrading', $operator);
    }

    if (array_key_exists('holder', $conditions)) {
      $query->condition('entity_type', $conditions['holder']->getEntityTypeId())
      ->condition('pid', $conditions['holder']->id());
    }

    if (array_key_exists('entity_types', $conditions)) {
      $query->condition('w.entity_type', $conditions['entity_types']);
    }

    if (!empty($conditions['fragment'])) {
      $string = '%'.db_like($conditions['fragment']).'%';
      $namelike->condition('w.name', $string, 'LIKE');
      $like = TRUE;
      //remember that it is only possible to match against holder names
      //if each of the holder types can have no more than one wallet.
      //which entitytypes are we considering? if none were passed, then all of them
      if (empty($conditions['entity_types'])) {
        $conditions['entity_types'] = array_keys(Exchange::walletableBundles());
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

        if ($entity_type_id == 'user') {
          $label_key = 'name';
        }
        else {
          $label_key = $entity_info->getKey('label');
        }

        if ($label_key) {
          $query->leftjoin($entity_table, $alias, "w.pid = $alias.uid");
          $namelike->condition($alias.'.'.$label_key, $string, 'LIKE');
        }
        else {
          drupal_set_message("Can't filter on ".$entity_type_id .' because there is no label field for that entityType', 'warning');
        }
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
    //we know that user is is one of the entities in this query
    if ($like) {
      $query->condition($namelike);
    }

    if ($limit) {
      //passing $limit = NULL gets converted to limit = 0, which is bad
      //however it is safe to say that if there is no limit there is no offset, right?
      $query->range($offset, $limit);
    }
    //the tag allows the query to be altered, can't remember specifically why
    return $query->addTag('walletFilter')->execute()->fetchcol();
  }

}

