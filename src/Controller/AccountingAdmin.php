<?php

/**
 * @file
 * Contains \Drupal\mcapi\Controller\AccountingAdmin.
 */

namespace Drupal\mcapi\Controller;

use Drupal\system\Controller\SystemController;
use Drupal\Core\Url;
use Drupal\mcapi\Mcapi;

/**
 * Returns responses for Wallet routes.
 */
class AccountingAdmin extends SystemController {

  /**
   * {@inheritdoc}
   */
  function systemAdminMenuBlockPage() {
    if (!Mcapi::enoughWallets($this->currentUser()->id())) {
      $message[] = $this->t("There aren't enough wallets for you to create a transaction.");
      if (Mcapi::maxWalletsOfBundle('user', 'user') == 1) {
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
    $renderable[] = parent::systemAdminMenuBlockPage();
    return $renderable;
  }

}
