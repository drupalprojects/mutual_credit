<?php

/**
 * @file
 *  Contains Drupal\mcapi_limits\Plugin\McapiLimitsBase.
 */

namespace Drupal\mcapi_limits\Plugin;

use Drupal\mcapi\Entity\CurrencyInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Component\Plugin\ConfigurablePluginInterface;

/**
 * Base class for Limits plugins.
 */
abstract class McapiLimitsBase implements McapiLimitsInterface {

  public $id;

  public $currency;

  /**
   * stores the limits settings from the currency, for convenience
   * @var array
   */
  protected $configuration;

  /**
   * @param CurrencyInterface $currency
   *
   * @param type $plugin_id
   *
   * @param array $definition
   */
  public function __construct(array $settings, $plugin_id, array $definition) {
    $this->currency = $settings['currency'];
    $this->id = $plugin_id;
    $this->setConfiguration($this->currency->getThirdPartySettings('mcapi_limits'));
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
    return [
      'override' => 0,
      'skip' => [],
      'display_relative' => FALSE
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    //we are relying in the inserted fields to validate themselves individually, so there is no validation added at the form level
    $subform['override'] = [
      '#title' => t('Allow wallet-level override'),
      '#description' => t('Settings on the user profiles override these general limits.'),
      '#type' => 'checkbox',
      '#default_value' => $this->configuration['override'],
      '#weight' => 5,
      '#states' => [
        'invisible' => [
          ':input[name="limits[limits_callback]"]' => ['value' => 'limits_none']
        ]
      ]
    ];
    $subform['skip'] = [
      '#title' => t('Skip balance limit check for the following transactions'),
      '#description' => t('Especially useful for mass transactions and automated transactions'),
      '#type' => 'checkboxes',
      //casting it here saves us worrying about the default, which is awkward
      '#default_value' => array_keys(array_filter($this->configuration['skip'])),
      //would be nice if this was pluggable, but not needed for the foreseeable
      '#options' => [
        'auto' => t("of type 'auto'"),
        'mass' => t("of type 'mass'"),
        'user1' => t("created by user 1"),
        'owner' => t("created by the currency owner"),
      ],
      '#states' => [
        'invisible' => [
          ':input[name="limits[limits_callback]"]' => ['value' => 'limits_none']
        ]
      ],
      '#weight' => 6,
    ];
    //we are relying in the inserted fields to validate themselves individually, so there is no validation added at the form level
    $subform['display_relative'] = [
      '#title' => t('Display perspective'),
      '#type' => 'radios',
      '#options' => [
        0 => t('Show absolute balance limits'),
        1 => t('Show spend/earn limits relative to the balance'),
      ],
      '#default_value' => intval($this->configuration['display_relative']),
      '#weight' => 10,
    ];
    return $subform;
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {

  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $config = [];
    if ($values['plugin'] != 'none') {
      foreach ($values['limits_settings'] as $key => $value) {
        $config[$key] = $value;
      }
    }
    $this->setConfiguration($config);
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() {
    return ['modules' => 'mcapi_limits'];
  }
}

