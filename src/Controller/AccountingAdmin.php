<?php

/**
 * @file
 * Contains \Drupal\mcapi\Controller\AccountingAdmin.
 */

namespace Drupal\mcapi\Controller;

use Drupal\system\Controller\SystemController;
use Drupal\Core\Url;

/**
 * Returns responses for Wallet routes.
 */
class AccountingAdmin extends SystemController {


  function systemAdminMenuBlockPage() {
    $renderable[] = parent::systemAdminMenuBlockPage();
    if (enough_wallets()->isForbidden()) {
      $usermax = \Drupal::config('mcapi.wallets')->get('entity_types')['user:user'];
      $message[] = $this->t("There aren't enough wallets for you to create a transaction.");
      if ($usermax > 1) {
        $message[] = $this->l(
          $this->t("Give yourself a(nother) wallet."),
          Url::fromRoute('mcapi.wallet.add.user', ['user' => \Drupal::currentUser()->id()])
        );
      }
      else {
        $message[] = $this->l(
          $this->t("Allow more wallets or more entities to own wallets."),
          Url::fromRoute('mcapi.admin_wallets')
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
