<?php

/**
 * @file
 * Contains \Drupal\mcapi\CurrencyAccessController.
 */

namespace Drupal\mcapi;

use Drupal\Core\Entity\EntityAccessController;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Defines an access controller for the Currency entity
 *
 * @see \Drupal\mcapi\Entity\Currency.
 */
class CurrencyAccessController extends EntityAccessController {

  /**
   * {@inheritdoc}
   */
  public function checkAccess(EntityInterface $entity, $operation, $langcode, AccountInterface $account) {
    //might be possible to remove this whole controller.
    //Currencies can be edited by only by their uid
    //there is nothing to view
    //need permission manage mcapi to create
    drupal_set_message('CurrencyAccessController::checkAccess($op). Owned by '.$entity->get('uid')->value);
    if ($operation == 'create') return $account->hasPermission('manage mcapi');
    elseif ($operation == 'edit') return $account->hasPermission('configure mcapi') || $account->id() == $entity->get('uid')->value;
    elseif ($operation == 'view') {
      return TRUE; //because there is no view page
    }
    else {
      mtrace();
	    drupal_set_message('unknown operation: '.$operation, 'error');
    };
  }

}
