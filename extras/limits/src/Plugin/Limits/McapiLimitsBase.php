<?php

/**
 * @file
 *  Contains Drupal\mcapi_limits\Plugin\Limits\McapiLimitsBase.
 */

namespace Drupal\mcapi_limits\Plugin\Limits;

use \Drupal\mcapi\Entity\CurrencyInterface;
use \Drupal\Core\Form\ConfigFormBase;

/**
 * Base class for Transitions for default methods.
 */
abstract class McapiLimitsBase implements McapiLimitsInterface {

  public $id;
  public $currency;
	protected $configuration;

  public function __construct(array $settings, $plugin_name, $definition) {
    $this->currency = $settings['currency'];
    $this->id = $plugin_name;
    $this->setConfiguration(\Drupal::config('mcapi.limits.'.$this->currency->id())->getRawData());
  }

  /**
   * {@inheritdoc}
   */
  public function getConfiguration() {
    return $this->configuration;
  }
  /**
   * {@inheritdoc}
   */
  public function setConfiguration(array $configuration) {
    $this->configuration = $configuration + $this->defaultConfiguration();
  }
  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return array(
    	'override' => 0,
      'skip' => array(),
      'display_relative' => FALSE
    );
  }

  /**
   * {@inheritdoc}
   */
	public function buildConfigurationForm(array $form, array &$form_state) {

	  //we are relying in the inserted fields to validate themselves individually, so there is no validation added at the form level
	  $subform['override'] = array(
	    '#title' => t('Allow wallet-level override'),
	    '#description' => t('Settings on the user profiles override these general limits.'),
	    '#type' => 'checkbox',
	    '#default_value' => $this->configuration['override'],
	    '#weight' => 5,
	    '#states' => array(
	      'invisible' => array(
	        ':input[name="limits[limits_callback]"]' => array('value' => 'limits_none')
	      )
	    )
	  );
	  $subform['skip'] = array(
	    '#title' => t('Skip balance limit check for the following transactions'),
	    '#description' => t('Especially useful for mass transactions and automated transactions'),
	    '#type' => 'checkboxes',
	    //casting it here saves us worrying about the default, which is awkward
	    '#default_value' => array_keys(array_filter($this->configuration['skip'])),
	    //would be nice if this was pluggable, but not needed for the foreseeable
	    '#options' => array(
	      'auto' => t("of type 'auto'"),
	      'mass' => t("of type 'mass'"),
	      'user1' => t("created by user 1"),
	      'owner' => t("created by the currency owner"),
	    ),
	    '#states' => array(
	      'invisible' => array(
	        ':input[name="limits[limits_callback]"]' => array('value' => 'limits_none')
	      )
	    ),
	    '#weight' => 6,
	  );
	  //we are relying in the inserted fields to validate themselves individually, so there is no validation added at the form level
	  $subform['display_relative'] = array(
      '#title' => t('Display perspective'),
      '#description' => t('Settings on the user profiles override these general limits.'),
      '#type' => 'radios',
	    '#options' => array(
	      0 => t('Show absolute balance limits'),
	  	  1 => t('Show spend/earn limits relative to the balance'),
	    ),
      '#default_value' => intval($this->configuration['display_relative']),
      '#weight' => 10,
	  );
    return $subform;
	}

	public function validateConfigurationForm(array &$form, array &$form_state) {

	}

	public function submitConfigurationForm(array &$form, array &$form_state) {
	  foreach ($form_state['values']['limits_settings'] as $key => $value) {
      $this->configuration[$key] = $value;
	  }
	}

	public function calculateDependencies() {
	  return array(
	  	'modules' => 'mcapi_limits'
	  );
	}
}

