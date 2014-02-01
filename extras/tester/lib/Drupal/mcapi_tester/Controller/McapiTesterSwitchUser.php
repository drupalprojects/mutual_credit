<?php
/**
 * @file
 * Contains \Drupal\mcapi_tester\Controller\McapiTesterSwitchuser.
 */

namespace Drupal\mcapi_tester\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\user\UserInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;

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
   */
  public function switchUser($name = NULL) {
    global $user;
    $module_handler = $this->moduleHandler();

    if ($uid = $this->currentUser()->id()) {
      $module_handler->invokeAll('user_logout', array($user));
    }
    if (isset($name) && $account = user_load_by_name($name)) {
      $old_uid = $uid;
      $user = $account;
      $user->timestamp = time() - 9999;
      if (!$old_uid) {
        // Switch from anonymous to authorized.
        drupal_session_regenerate();
      }
      $module_handler->invokeAll('user_login', array($user));
    }
    elseif ($uid) {
      session_destroy();
    }
    $destination = drupal_get_destination();
    $url = $this->urlGenerator()->generateFromPath($destination['destination'], array('absolute' => TRUE));

    return new RedirectResponse($url);
  }
}
