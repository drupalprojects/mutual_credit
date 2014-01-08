<?php

/**
 * @file
 * Contains \Drupal\mcapi\TransactionInterface.
 */

namespace Drupal\mcapi;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityStorageControllerInterface;

interface TransactionInterface extends EntityInterface {
  /**
   * Add the serial number and other default values
   *
   * @param EntityStorageControllerInterface $storage_controller
   */
  public function preSave(EntityStorageControllerInterface $storage_controller);

  /**
   * Update the index table and save the worths if they are in their own table
   *
   * @param EntityStorageControllerInterface $storage_controller
   * @param boolean $update
   *   TRUE if the entity already exists
   */
  public function postSave(EntityStorageControllerInterface $storage_controller, $update = TRUE);

  /**
   * Set the transaction entity defaults
   *
   * @param EntityStorageControllerInterface $storage_controller
   * @param array $values
   */
  public static function preCreate(EntityStorageControllerInterface $storage_controller, array &$values);

  /**
   *
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions($entity_type);
  /**
   * Validate a transaction, and generate the children by calling hook_transaction_children,
   * and validate the children
   * This function calls itself!
   * Adds exceptions to each transaction's exception array
   * Does NOT throw any errors
   *
   * @return array $messages
   *   a flat list of non-fatal messages from all transactions in the cluster
   */
  public function validate();

  /**
   * return a render array of 'operation' links for the current user on $this transaction
   *
   * @param string $mode
   *   one of 'page', 'ajax, or 'modal'
   * @param boolean $view
   *   TRUE if the 'view' link should be included.
   */
  public function links($mode = 'page', $view = FALSE);


}