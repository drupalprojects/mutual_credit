<?php
/**
 * @file
 * Contains \Drupal\mcapi_tester\Controller\McapiTesterSwitchuser.
 */

namespace Drupal\mcapi_tester\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\user\UserInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Returns responses for devel module routes.
 */
class McapiTesterSwitchuser extends ControllerBase {
  /**
   * Switches to a different user.
   *
   * We don't call session_save_session() because we really want to change users.
   * Usually unsafe!
   *
   * @param string $name
   *   The username to switch to, or NULL to log out.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *
   * @todo replace this with the devel block when it starts working!
   */
  public function switchUser($name = NULL) {
    user_logout();
    if ($account = user_load_by_name($name)) {
      user_login_finalize($account);
    }
  }
}
