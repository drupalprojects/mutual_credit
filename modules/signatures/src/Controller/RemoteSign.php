<?php

namespace Drupal\mcapi_signatures\Controller;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Controller\ControllerBase;
use \Drupal\mcapi\Entity\Transaction;

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
      drupal_set_message(t('The signature had already been added.'));
      if ($this->currentUser()->isAuthenticated()) {
        $this->redirect('user.page');
      }
      else {
        $this->redirect('user.login');
      }
    }

  }

}
