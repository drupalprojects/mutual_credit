<?php

/**
 * @file
 * Contains \Drupal\mcapi_cc\Controller\GoSettings.
 */

namespace Drupal\mcapi_cc\Controller;


/**
 * redirects to first intertrading wallet
 */
class GoSettings extends \Drupal\system\Controller\SystemController {

  function WalletOneEdit() {
    return $this->redirect('entity.mcapi_wallet.edit_form', ['mcapi_wallet' => 1]);

  }

}
