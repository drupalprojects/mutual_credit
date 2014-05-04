<?php

/**
 * @file
 *  Contains Drupal\mcapi_limits\Plugin\Limits\None
 *
 */

namespace Drupal\mcapi_limits\Plugin\Limits;

use \Drupal\mcapi_limits\McapiLimitsBase;
use \Drupal\mcapi_limits\McapiLimitsInterface;
use \Drupal\Core\Entity\EntityInterface;

/**
 * No balance limits
 *
 * @Limits(
 *   id = "none",
 *   label = @Translation("None"),
 *   description = @Translation("No limits")
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

  public function checkPayer(EntityInterface $wallet, $diff){}
  public function checkPayee(EntityInterface $wallet, $diff){}

  public function getBaseLimits(EntityInterface $wallet){
  	return array('min' => NULL, 'max' => NULL);
  }

  public function getLimits(EntityInterface $wallet){
    return array(
    	'min' => NULL,
      'max' => NULL
    );
  }
}
