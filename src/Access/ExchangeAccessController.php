<?php

/**
 * @file
 * Contains \Drupal\mcapi\Access\ExchangeAccessController.
 */

namespace Drupal\mcapi\Access;

use Drupal\Core\Entity\EntityAccessController;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
//use Symfony\Component\HttpFoundation\Request;
use Drupal\Core\Language\Language;
use Drupal\mcapi\Entity\ExchangeInterface;


/**
 * Defines an access controller option for the mcapi_wallet entity.
 */
class ExchangeAccessController extends EntityAccessController {

  /**
   * {@inheritdoc}
   */
  public function checkAccess(EntityInterface $exchange, $op, $langcode, AccountInterface $account) {
    if ($op == 'view') {
      //this should be congruent with Drupal\mcapi\Plugins\views\filter\ExchangeVisibility
      $visib = $exchange->get('visibility')->value;
      if (
        $account->hasPermission('configure mcapi') ||
        $visib == 'public' ||
        ($visib == 'restricted' && $account->id()) ||
        ($visib == 'private' && $exchange->is_member(user_load($account->id())))
        ) {
        return TRUE;
      }
      return FALSE;
    }
    elseif ($op == 'delete') {
      return (user_access('manage mcapi') && $this->deletable($exchange));
    }
    elseif($op == 'update') {
      return user_access('manage mcapi') || $exchange->is_manager();
    }
    elseif($op == 'manage') {
      return user_access('manage mcapi') || $exchange->is_manager();
    }
  }


  /**
   * {@inheritdoc}
   */
  function deletable(ExchangeInterface $exchange) {
    if ($exchange->get('status')->value) {
      $exchange->reason = t('Exchange must be disabled');
      return FALSE;
    }
    if (\Drupal::config('mcapi.misc')->get('indelible')) {
      $exchange->reason = t("Indelible Accounting flag is enabled.");
      return FALSE;
    }
    if (count($exchange->intertrading_wallet()->history())) {
      $exchange->reason = t('Exchange intertrading wallet has transactions');
      return FALSE;
    }
    //if the exchange has wallets, even orphaned wallets, it can't be deleted.
    $conditions = array('exchanges' => $exchange->id());
    $wallet_ids = \Drupal::EntityManager()->getStorage('mcapi_wallet')->filter($conditions);
    if (count($wallet_ids > 1)){
      $exchange->reason = t('The exchange still owns wallets: @nums', array('@nums' => implode(', ', $wallet_ids)));
      return FALSE;
    }
    return TRUE;
  }


}
