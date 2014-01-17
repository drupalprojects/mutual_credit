<?php

/**
 * @file
 * Contains Drupal\mcapi\Plugin\WalletAccess\Inherit
 */

namespace Drupal\mcapi\Plugin\WalletAccess;


/**
 * Grant the same access to the wallet as to the parent entity.
 *
 * @WalletAccess(
 *   id = "inherit",
 *   label = @Translation("Whoever can view the parent entity"),
 *   description = @Translation("Inherit access from the parent entity"),
 * )
 */
class Inherit { //implements walletAccessInterface

  /**
   * Only this one function needed I think
   *
   * @param unknown $wallet
   * @param unknown $account
   */
  function check($wallet, $account) {
    return TRUE;
  }

}
