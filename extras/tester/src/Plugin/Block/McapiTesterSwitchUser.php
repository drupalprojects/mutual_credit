<?php

/**
* @file
* Contains \Drupal\mcapi_tester\Plugin\Block\McapiTesterSwitchUser.
* Self contained, temp block because devel module isn't reliable
*/

namespace Drupal\mcapi_tester\Plugin\Block;

use Drupal\block\BlockBase;
use Drupal\Core\Session\AccountInterface;
use Drupal\user\Entity\User;
use Drupal\mcapi\Entity\Exchange;

/**
 * Provides a block for switching users.
 *
 * @Block(
 *   id = "mcapi_tester_switch_user",
 *   admin_label = @Translation("Switch user"),
 *   category = @Translation("Community Accounting")
 * )
 */
class McapiTesterSwitchUser extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function blockAccess(AccountInterface $account) {
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $links = $this->switchUserList();
    $username = \Drupal::CurrentUser()->getUsername();
    if (!empty($links)) {
      $build = array(
        'current' => array('#markup' => "Currently: $username<br/>"),
      );
      if (\Drupal::currentUser()->id()) {
        $path = 'switch/' . $username;
        $dest = drupal_get_destination();
        $build['help'] = array('#markup' => "Switch user in one click isn't working:");
        $build['logout'] = array('#markup' => l('Log out', $path, array('query' => $dest + array('token' => \Drupal::csrfToken()->get($path . '|' . $dest['destination'])))));
      }
      else {
        $build['devel_links'] = array('#theme' => 'links', '#links' => $links);
      }
      return $build;
    }
  }

  /**
   * Provides the Switch user list.
   */
  public function switchUserList() {
    global $user;

    $links = array();
    $query = db_select('users', 'u');
    $query->addField('u', 'uid');
    $query->addField('u', 'access');
    $query->distinct();
    $query->condition('u.uid', 0, '>');
    $query->condition('u.uid', \Drupal::currentUser()->id(), '<>');
    $query->condition('u.status', 0, '>');
    $query->orderBy('u.access', 'DESC');
    //$query->range(0, 10);
    $uids = $query->execute()->fetchCol();
    $accounts = User::loadMultiple($uids);

    $dest = drupal_get_destination();
    foreach ($accounts as $account) {
      $path = 'switch/' . $account->name->value;
      $belongs_to = current(Exchange::referenced_exchanges($account, TRUE));
      $links[$account->id()] = array(
        'title' => user_format_name($account),
        'href' => $path,
        'query' => $dest + array('token' => \Drupal::csrfToken()->get($path . '|' . $dest['destination'])),
        'html' => TRUE,
        'last_access' => $account->access->value,
      );
    }
    return $links;
  }

}
