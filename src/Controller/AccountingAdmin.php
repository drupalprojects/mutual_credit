<?php

namespace Drupal\mcapi\Controller;

use Drupal\system\Controller\SystemController;

/**
 * Returns responses for Wallet routes.
 */
class AccountingAdmin extends SystemController {


  /**
   * {@inheritdoc}
   */
  public function systemAdminMenuBlockPage() {
    // Show a warning if there aren't enough wallets to trade
    $wallets = $this->entityTypeManager()->getStorage('mcapi_wallet')->getQuery()->count()->execute();
    if ($wallets < 2) {
      $message = $this->t("There aren't enough wallets for you to create a transaction. Wallets are at the same time as, or on the canonical page of, the entities that will hold them.");
      drupal_set_message($message, 'warning');
    }
    $renderable[] = parent::systemAdminMenuBlockPage();
    return $renderable;
  }

}
