<?php

/**
 * @file
 * Contains \Drupal\simple_access\GroupAccessController.
 */

namespace Drupal\mcapi;

use Drupal\Core\Entity\EntityAccessController;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Defines an access controller for the transaction entity.
 *
 * @see \Drupal\simple_access\Entity\Group.
 */
class TransactionAccessController extends EntityAccessController {

	function __construct() {
		/*
		$this->operation_manager = \Drupal::service('plugin.manager.mcapi.transaction_operation');
	  foreach ($this->operation_manager->getDefinitions() as $op => $info) {
	  	$operations[$op] = new $info['class'];
	  }
	  */
	}

  /**
   * {@inheritdoc}
   */
  public function checkAccess(EntityInterface $transaction, $op, $langcode, AccountInterface $account) {
    if ($op == 'op') {
      //there is probably a better way of writing the router so the op is passed as a variable
      $op = \Drupal::request()->attributes->get('op');
    }
    $operations = transaction_operations();
   	return ($operations[$op]->opAccess($transaction));
  }


}
