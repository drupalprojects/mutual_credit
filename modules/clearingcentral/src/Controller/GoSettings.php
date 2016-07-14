<?php

namespace Drupal\mcapi_cc\Controller;

use Drupal\system\Controller\SystemController;

/**
 * Redirects to first intertrading wallet.
 */
class GoSettings extends SystemController {

  /**
   * Redirect to edit wallet one.
   */
  public function walletOneEdit() {
    return $this->redirect('entity.mcapi_wallet.edit_form', ['mcapi_wallet' => 1]);

  }

}
