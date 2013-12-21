<?php

/**
 * @file
 *  Contains Drupal\mcapi_limits\Plugin\Limits\None
 *
 */

namespace Drupal\mcapi_limits\Plugin\Limits;

use Drupal\mcapi\TransactionInterface;
//use Drupal\mcapi\CurrencyInterface;
use \Drupal\Core\Config\ConfigFactory;
use \Drupal\mcapi_limits\McapiLimitsBase;
use \Drupal\mcapi_limits\McapiLimitsInterface;
use \Drupal\Core\Session\AccountInterface;

/**
 * No balance limits
 *
 * @Limits(
 *   id = "none",
 *   label = @Translation("None"),
 *   description = @Translation("No limits"),
 *   settings = {
 *     "min" = "0",
 *     "max" = "0"
 *   }
 * )
 */
class None extends McapiLimitsBase implements McapiLimitsInterface {

  public function settingsForm() {
    //$conf = $config->get('blah');
    //parent::settingsForm($form, $config);
    return array('empty' => array(
    	'#markup' => t('There are no settings for this plugin')
    ));
  }

  public function checkPayer(AccountInterface $account, $diff){}
  public function checkPayee(AccountInterface $account, $diff){}

  public function getLimits(AccountInterface $account = NULL){
  	return array('min' => NULL, 'max' => NULL);
  }
}
