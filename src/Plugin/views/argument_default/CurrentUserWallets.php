<?php

/**
 * @file
 * Definition of Drupal\mcapi\Plugin\views\argument_default\CurrentUserWallets.
 */

namespace Drupal\mcapi\Plugin\views\argument_default;

use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Plugin\views\argument_default\ArgumentDefaultPluginBase;
use Drupal\user\Entity\User;

/**
 * The fixed argument default handler.
 *
 * @ingroup views_argument_default_plugins
 *
 * @ViewsArgumentDefault(
 *   id = "current_user_wallets",
 *   title = @Translation("Wallets of the current user")
 * )
 */
class CurrentUserWallets extends ArgumentDefaultPluginBase {

  /**
   * Return the default argument.
   */
  public function getArgument() {
    $account = User::load(\Drupal::currentUser()->id());
    $ids = \Drupal\mcapi\Storage\WalletStorage::getOwnedWalletIds($account);
    return implode('+', $ids);
  }

}
