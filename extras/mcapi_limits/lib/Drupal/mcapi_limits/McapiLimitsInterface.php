<?php

/**
 * @file
 * Contains \Drupal\mcapi\McapiLimitsInterface.
 */

namespace Drupal\mcapi_limits;

use Drupal\mcapi\TransactionInterface;
//use Drupal\mcapi\CurrencyInterface;
use Drupal\Core\Config\ConfigFactory;
use \Drupal\Core\Session\AccountInterface;

interface McapiLimitsInterface {

  public function settingsForm();

  public function checkPayer(AccountInterface $account, $diff);
  public function checkPayee(AccountInterface $account, $diff);

  public function getLimits(AccountInterface $account = NULL);

}
