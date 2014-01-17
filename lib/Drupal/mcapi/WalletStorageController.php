<?php

/**
 * @file
 * Contains \Drupal\mcapi\WalletStorageController.
 * this may be needed to write the entity references
 * if not, maybe it can be removed.
 */

namespace Drupal\mcapi;

use Drupal\Core\Entity\FieldableDatabaseStorageController;

// @todo do we need an interface for this?
class WalletStorageController extends FieldableDatabaseStorageController {

  function create(array $values) {
//print_r($this->entityType);die('wallet:create');

//    $values['access'] = 'inherit';//seems to make no difference
    return parent::create($values);
  }

  /**
   *
   */
  public function delete(array $transactions) {
  }

}
