<?php

namespace Drupal\mcapi_exchanges\Overrides;

use Drupal\group\Access\GroupAccessResult;
use Drupal\group\Entity\Group;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Routing\Access\AccessInterface;

/**
 * Access check for user registration routes.
 */
class RegisterAccessCheck implements AccessInterface {

  /**
   * Checks access.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The currently logged in account.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  public function access(AccountInterface $account, $group) {
    $user_settings = \Drupal::config('user.settings');
    $group = Group::load($group);
    return GroupAccessResult::allowedIfHasGroupPermission($group, $account, 'administer members')
      ->orIf(
        AccessResult::allowedIf(
          $account->isAnonymous() &&
          $user_settings->get('register') != USER_REGISTER_ADMINISTRATORS_ONLY
        )
      )->cacheUntilConfigurationChanges($user_settings);
  }

}

