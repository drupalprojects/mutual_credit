<?php

/**
 * @file
 * Contains \Drupal\mcapi_signatures\Controller\RemoteSign.
 *
 */

namespace Drupal\mcapi_signatures\Controller;
use \Drupal\mcapi\Entity\Transaction;

/**
 * Returns responses for Wallet routes.
 */
class RemoteSign extends \Drupal\Core\Controller\ControllerBase {

  private $signatures;

  function __construct($signatures) {
    $this->signatures = $signatures;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(\Symfony\Component\DependencyInjection\ContainerInterface $container) {
    return new static(
      $container->get('mcapi.signatures')
    );
  }

  /**
   * if the hash is invalid go to 'user'
   * if the transaction is signed
   *   put a message & redirect
   * if the transaction needs signing
   *   if we are logged in
   *     if the uid is different
   *       log out
   *   if we are logged out
   *     log in
   *   Sign the transaction
   *   set message
   *   redirect to the transaction
   * else redirect somewhere else
   *
   * @param integer $serial
   * @param integer $uid
   * @param string $passed_hash
   */
  public function sign($serial, $uid, $passed_hash) {
    if ($passed_hash != mcapi_signature_hash($uid, $serial)) {
      drupal_set_message(t('Invalid link'));
      return $this->redirect('entity.user.canonical');
    }
    $transaction = Transaction::loadBySerial($serial);
    if ($this->signatures->setTransaction($transaction)->waitingOn($uid)) {
      $account = $this->currentUser();
      if ($account->isAuthenticated()) {
        // The current user is already logged in.
        if ($account->id() != $uid) {
          user_logout();
        }
      }
      if ($account->isAnonymous()) {
        $user = User::load($uid);
        user_login_finalize($user);
      }
      $this->signatures->sign($uid);
      $transaction->save();
      $this->redirect('entity.mcapi_transaction.canonical', ['mcapi_transaction' => $serial]);
    }
    else {
      drupal_set_message('The signature had already been added.');
      if ($this->currentUser()->isAuthenticated()) {
        $this->redirect('user.page');
      }
      else {
        $this->redirect('user.login');
      }
    }

  }

}
