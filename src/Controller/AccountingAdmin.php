<?php

/**
 * @file
 * Contains \Drupal\mcapi\Controller\AccountingAdmin.
 */

namespace Drupal\mcapi\Controller;

use Drupal\system\Controller\SystemController;
use Drupal\Core\Url;
use Drupal\mcapi\Access\TransactionAccessControlHandler;

/**
 * Returns responses for Wallet routes.
 */
class AccountingAdmin extends SystemController {


  function systemAdminMenuBlockPage() {
    $renderable[] = parent::systemAdminMenuBlockPage();
    if (TransactionAccessControlHandler::enoughWallets($this->currentUser())->isForbidden()) {
      $message[] = $this->t("There aren't enough wallets for you to create a transaction.");
      if (mcapi_one_wallet_per_user_mode()) {
        $message[] = $this->l(
          $this->t("Allow more wallets or more entities to own wallets."),
          Url::fromRoute('mcapi.admin_wallets')
        );
      }
      else {
        $message[] = $this->l(
          $this->t("Give yourself a(nother) wallet."),
          Url::fromRoute('mcapi.wallet.add.user', ['user' => $this->currentUser()->id()])
        );
      }
      //don't both checking for create user access
      $message[] = $this->l(
        $this->t("Or create another user."),
        Url::fromRoute('user.admin_create')
      );
      $renderable['warning'] = ['#markup' => implode(' ', $message), '#weight' => -1];
    }

    return $renderable;
  }

}