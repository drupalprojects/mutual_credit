<?php

/**
 * @file
 *  Contains Drupal\mcapi_limits\McapiLimitsBase.
 */

namespace Drupal\mcapi_limits;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Session\CurrencyInterface;

/**
 * Base class for Operations for default methods.
 */
abstract class McapiLimitsBase extends ConfigEntityBase implements McapiLimitsInterface {

	public $currency;

  public function __construct(CurrencyInterface $currency) {
    $this->currency = $currency;
  }

	/*
	 * basic settings form which individual operations can alter
	 */
	public function settingsForm() {

	  //we are relying in the inserted fields to validate themselves individually, so there is no validation added at the form level
	  $form['personal'] = array(
	    '#title' => t('Personal limits'),
	    '#description' => t('Settings on the user profiles override these general limits.'),
	    '#type' => 'checkbox',
	    '#default_value' => $this->currency->limits_settings['personal'],
	    '#weight' => 5,
	    '#states' => array(
	      'invisible' => array(
	        ':input[name="limits[limits_callback]"]' => array('value' => 'limits_none')
	      )
	    )
	  );
	  $form['skip'] = array(
	    '#title' => t('Skip balance limit check'),
	    '#description' => t('Especially useful for mass transactions and automated transactions'),
	    '#type' => 'checkboxes',
	    //casting it here saves us worrying about the default, which is awkward
	    '#default_value' => (array)$this->currency->limits_settings['skip'],
	    //would be nice if this was pluggable, but not needed for the foreseeable
	    '#options' => array(
	      'auto' => t("for transactions of type 'auto'"),
	      'mass' => t("for transactions of type 'mass'"),
	      'user1' => t("for transactions created by user 1"),
	      'owner' => t("for transactions created by the currency owner"),
	      'reservoir' => t("for the reservoir account")
	    ),
	    '#states' => array(
	      'invisible' => array(
	        ':input[name="limits[limits_callback]"]' => array('value' => 'limits_none')
	      )
	    ),
	    '#weight' => 6,
	  );
    return $form;
	}

	public function getLimits(AccountInterface $account) {
	  //check for overrides
	  if ($this->currency->limits_settings['personal']) {
	    //and if the $account has any overrides
	    if (FALSE) {
	      //send back the limits from the user object
	      return array('min' => -100, 'max' => 100);
	    }
	  }
	  return $this->getBaseLimits($account);
	}

  //get the personal overrides
  abstract function getBaseLimits(AccountInterface $account);
}

