<?php

/**
 * @file
 * Contains \Drupal\mcapi\ExchangeAccessController.
 */

namespace Drupal\mcapi;

use Drupal\Core\Entity\EntityAccessController;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
//use Symfony\Component\HttpFoundation\Request;
use Drupal\Core\Language\Language;


/**
 * Defines an access controller option for the mcapi_wallet entity.
 */
class ExchangeAccessController extends EntityAccessController {

  /**
   * {@inheritdoc}
   */
  public function checkAccess(EntityInterface $exchange, $op, $langcode, AccountInterface $account) {
    //this should be congruent with Drupal\mcapi\Plugins\views\filter\ExchangeVisibility
    $visib = $exchange->get('visibility')->value;
    if (
      $account->hasPermission('configure mcapi') ||
      $visib == 'public' ||
      ($visib == 'restricted' && $account->id()) ||
      ($visib == 'private' && $exchange->member(user_load($account->id())))
      ) {
      return TRUE;
    }
    return FALSE;
  }


}
