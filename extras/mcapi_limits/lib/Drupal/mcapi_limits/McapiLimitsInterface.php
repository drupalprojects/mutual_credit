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
   * returns an array with keys min & max
   */
  public function getLimits(AccountInterface $account = NULL);

  /*
   * returns a render array
   */
  public function view(AccountInterface $account);

}
