<?php

/**
 * @file
 * Contains \Drupal\mcapi\Access\CurrencyAccessController.
 */

namespace Drupal\mcapi\Access;

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
    switch ($operation) {
    	case 'view':
    	  return TRUE;
    	case 'create':
  	  case 'delete':
    	  return $account->hasPermission('manage mcapi') && $this->deletable($entity);
    	case 'update':
    	//case 'edit':
    	  return $account->hasPermission('configure mcapi') || $account->id() == $entity->getOwnerId();

    	default:
    	  echo $operation;
        drupal_set_message('unknown currency operation: '.$operation, 'error');
    };
  }

  function deletable($mcapi_currency) {

    if (\Drupal::config('mcapi.misc')->get('indelible'))return FALSE;

    $all_transactions = $mcapi_currency->transactions(array('state' => 0));
    $deleted_transactions = $mcapi_currency->transactions(array('state' => 0));

    return $all_transactions == $deleted_transactions;
  }

}