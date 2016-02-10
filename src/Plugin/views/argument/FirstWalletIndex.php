<?php

/**
 * @file
 * Contains \Drupal\mcapi\Plugin\views\argument\FirstWalletIndex.
 */

namespace Drupal\mcapi\Plugin\views\argument;

use Drupal\views\Plugin\views\argument\ArgumentPluginBase;
use Drupal\user\Entity\User;
use Drupal\mcapi\Mcapi;

/**
 * Argument handler to convert a user id to that user's first wallet, on the transaction index table
 *
 * @ViewsArgument("mcapi_first_wallet_index")
 */
class FirstWalletIndex extends ArgumentPluginBase {

  /**
   * {@inheritdoc}
   */
  public function query($group_by = FALSE) {
    $this->ensureMyTable();
    $wids = Mcapi::walletsOf(User::load($this->argument));
    $this->query->addWhere(0, $this->tableAlias.'.wallet_id', reset($wids));
  }
}
