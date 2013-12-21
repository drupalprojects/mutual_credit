<?php

/**
 * @file
 * Contains \Drupal\mcapi\McapiLimitsInterface.
 */

namespace Drupal\mcapi_limits;

use Drupal\mcapi\TransactionInterface;
use Drupal\Core\Config\ConfigFactory;
use \Drupal\Core\Session\AccountInterface;

interface McapiLimitsInterface {

  /*
   * return a form render array
   */
  public function settingsForm();

  /*
   * return TRUE or FALSE
   */
  public function checkPayer(AccountInterface $account, $diff);

  /*
   * return TRUE or FALSE
  */
  public function checkPayee(AccountInterface $account, $diff);

  /*
   * get the limits, overridden if necessary by the personal limits
  */
  public function getLimits(AccountInterface $account);

  /*
   * get the limits as defined by any given plugin
  */
  public function getBaseLimits(AccountInterface $account);

  /*
   * returns a render array
   */
  public function view(AccountInterface $account);

}
