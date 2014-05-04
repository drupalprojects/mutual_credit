<?php

/**
 * @file
 * Contains \Drupal\mcapi\McapiLimitsInterface.
 */

namespace Drupal\mcapi_limits;

//note there is no walletInterface, though perhaps there should be
use \Drupal\Core\Entity\EntityInterface;

interface McapiLimitsInterface {

  /*
   * Settings to appear on the currency edit form
   * @return array
   *   form elements
   */
  public function settingsForm();

  /*
   * check whether the wallet can have the transaction diff subtracted
   * @param EntityInterface $wallet
   *
   * @param array diff
   *   an array of integer differences keyed by currency
   *
   * @return boolean
   *   TRUE if the wallet can be adjusted without transgression of the limits
   */
  public function checkPayer(EntityInterface $wallet, $diff);

  /*
   * check whether the wallet can have the transaction diff subtracted
   * @param EntityInterface $wallet
   *
   * @param array diff
   *   an array of integer differences keyed by currency
   *
   * @return boolean
   *   TRUE if the wallet can be adjusted without transgression of the limits
   */
  public function checkPayee(EntityInterface $wallet, $diff);

  /*
   * get the limits according to the plugin settings
   * @param EntityInterface $wallet
   *
   * @return array
   *   native currency amounts keyed with 'min' and 'max'
   */
  public function getLimits(EntityInterface $wallet);

}
