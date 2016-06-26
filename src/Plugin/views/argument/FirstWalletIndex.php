<?php

namespace Drupal\mcapi\Plugin\views\argument;

use Drupal\views\Plugin\views\argument\ArgumentPluginBase;
use Drupal\user\Entity\User;
use Drupal\mcapi\Mcapi;

/**
 * Argument handler to convert a user id to that user's first wallet.
 *
 * For queries only on the transaction index table.
 *
 * @ViewsArgument("mcapi_first_wallet_index")
 */
class FirstWalletIndex extends ArgumentPluginBase {

  /**
   * {@inheritdoc}
   */
  public function query($group_by = FALSE) {
    $this->ensureMyTable();
    $wid = Mcapi::firstWalletIdOfEntity(User::load($this->argument));
    $this->query->addWhere(0, $this->tableAlias . '.wallet_id', $wid);
  }

}
