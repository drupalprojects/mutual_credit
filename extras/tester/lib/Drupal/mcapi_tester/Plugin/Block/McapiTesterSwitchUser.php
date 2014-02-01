<?php

/**
* @file
* Contains \Drupal\mcapi_tester\Plugin\Block\McapiTesterSwitchUser.
* Self contained, temp block because devel module isn't reliable
*/

namespace Drupal\mcapi_tester\Plugin\Block;

use Drupal\block\BlockBase;
use Drupal\block\Annotation\Block;
use Drupal\Core\Annotation\Translation;
use Drupal\Core\Session\AccountInterface;

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
  public function access(AccountInterface $account) {
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $links = $this->switchUserList();
    if (!empty($links)) {
      //drupal_add_css(drupal_get_path('module', 'devel') . '/css/devel.css');
      $build = array(
        'help' => array('#markup' => 'Username (exchange id)'),
        'devel_links' => array('#theme' => 'links', '#links' => $links),
        'warning' => array('#markup' => "sometimes doesn't work"),
      );
      return $build;
    }
  }

  /**
   * Provides the Switch user list.
   */
  public function switchUserList() {
    global $user;

    $links = array();
    $dest = drupal_get_destination();
    $query = db_select('users', 'u');
    $query->addField('u', 'uid');
    $query->addField('u', 'access');
    $query->distinct();
    $query->condition('u.uid', 0, '>');
    $query->condition('u.status', 0, '>');
    $query->orderBy('u.access', 'DESC');
    $query->range(0, 10);
    $uids = $query->execute()->fetchCol();
    $accounts = user_load_multiple($uids);

    foreach ($accounts as $account) {
      $path = 'switch/' . $account->name->value;
      $belongs_to = current(referenced_exchanges($account));
      $links[$account->id()] = array(
        'title' => user_format_name($account) .(is_object($belongs_to) ? ' ('.$belongs_to->id() .')' : ''),
        'href' => $path,
        'query' => $dest + array('token' => drupal_get_token($path . '|' . $dest['destination'])),
        'html' => TRUE,
        'last_access' => $account->access->value,
      );
    }

    if (array_key_exists($uid = $user->id(), $links)) {
      $links[$uid]['title'] = '<strong>' . $links[$uid]['title'] . '</strong>';
    }
    return $links;
  }

}
