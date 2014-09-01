<?php

namespace Drupal\mcapi\Access;

/**
 * @file
 * Contains \Drupal\mcapi\Access\TransactionAccessController.
 */

use Drupal\Core\Entity\EntityAccessController;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\HttpFoundation\Request;
use Drupal\Core\Language\LanguageInterface;

/**
 * Defines an access controller option for the mcapi_transaction entity.
 */
class TransactionAccessController extends EntityAccessController {

  /**
   * {@inheritdoc}
   */
  public function access(EntityInterface $transaction, $transition, $langcode = LanguageInterface::LANGCODE_DEFAULT, AccountInterface $account = NULL) {
    if ($transition == 'transition') {
      $transition = \Drupal::RouteMatch()->getparameter('transition');
//TODO delete this $transition = \Drupal::request()->attributes->get('transition');
    }
    if (empty($account)) {
      $account = \Drupal::currentUser();
    }
    if ($plugin = \Drupal::service('mcapi.transitions')->getPlugin($transition)) {
      return $plugin->opAccess($transaction, $account);
    }
    return FALSE;
  }
}
