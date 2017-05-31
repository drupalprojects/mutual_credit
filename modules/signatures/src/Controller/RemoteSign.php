<?php

namespace Drupal\mcapi_signatures\Controller;

use Drupal\mcapi\Entity\Transaction;
use Drupal\mcapi\Storage\TransactionStorage;
use Drupal\user\Entity\User;
use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Add a signature from a user who may not be logged in.
 *
 * Depends on a token generated in the mcapi_signatures_mail().
 */
class RemoteSign extends ControllerBase {

  private $signatures;

  /**
   * Constructor.
   */
  public function __construct($signatures) {
    $this->signatures = $signatures;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('mcapi.signatures')
    );
  }

  /**
   * Sign the transaction if the hash is correct.
   *
   * @param int $serial
   *   Serial number of a transaction.
   * @param int $uid
   *   User ID.
   * @param string $passed_hash
   *   Hash which hopefully corresponds to #serial and $uid.
   */
  public function sign($serial, $uid, $hash) {
    if ($hash != mcapi_signature_hash($uid, $serial)) {
      drupal_set_message(t('Invalid link'));
      return $this->redirect('entity.user.canonical', ['user' => User::load($uid)]);
    }
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
    $transaction = TransactionStorage::loadBySerial($serial);
    if ($transaction instanceof Transaction) {
      if ($this->signatures->setTransaction($transaction)->waitingOn($uid)) {
        $this->signatures->sign($uid);
        $transaction->save();
        $this->redirect('entity.mcapi_transaction.canonical', ['mcapi_transaction' => $serial]);
      }
      else {
        drupal_set_message(t('The signature had already been added.'));
      }
    }
    else {
      drupal_set_message('Transaction does not exist or has been deleted', 'error');
    }
    $this->redirect('user.page');
  }

}
