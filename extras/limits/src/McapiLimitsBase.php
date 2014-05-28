<?php

/**
 * @file
 *  Contains Drupal\mcapi_limits\McapiLimitsBase.
 */

namespace Drupal\mcapi_limits;

use \Drupal\Core\Entity\EntityInterface;
use \Drupal\mcapi\Entity\CurrencyInterface;
use \Drupal\Core\Config\Entity\ConfigEntityBase;

/**
 * Base class for Transitions for default methods.
 */
abstract class McapiLimitsBase extends ConfigEntityBase implements McapiLimitsInterface {

  public $currency;
	public $limits_settings;

  public function __construct(array $config) {
    $this->currency = $config['currency'];
    $this->limits_settings = $this->currency->get('limits_settings');
  }

	/**
	 * @see \Drupal\mcapi_limits\McapiLimitsInterface::settingsForm()
	 */
	public function settingsForm() {

	  //we are relying in the inserted fields to validate themselves individually, so there is no validation added at the form level
	  $form['override'] = array(
	    '#title' => t('Allow wallet-level override'),
	    '#description' => t('Settings on the user profiles override these general limits.'),
	    '#type' => 'checkbox',
	    '#default_value' => $this->limits_settings['override'],
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
	    '#default_value' => (array)$this->limits_settings['skip'],
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


  /**
   * @see \Drupal\mcapi_limits\McapiLimitsInterface::getBaseLimits()
   */
  abstract function getLimits(EntityInterface $wallet);
}

