<?php

/**
 * @file
 * Contains \Drupal\mcapi\Plugin\TransitionInterface.
 *
 * @todo more consistent naming of the form injection functions and their validators
 */

namespace Drupal\mcapi\Plugin;

use Drupal\mcapi\TransactionInterface;
use Drupal\Core\Plugin\PluginFormInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\Component\Plugin\ConfigurablePluginInterface;

interface TransitionInterface extends ConfigurablePluginInterface {

  /**
   * Because we can't pass the transaction as an argument when initialising the
   * plugin, we use this function instead
   *
   * @param TransactionInterface $transaction
   */
  function setTransaction(TransactionInterface $transaction);

  /**
   * inject something into the transition form
   * the values will be passed into the transition execute function
   *
   * @param array $form
   *
   * @return array
   *   Renderable FormAPI elements
   */
 function form(array &$form);

  /**
   * validate the transition form
   * used rarely
   *
   * @param array $form
   * @param FormStateInterfaceInterface $form_state
   *
   * @return array
   *   Renderable FormAPI elements
   */
 function validateForm(array $form, FormStateInterface $form_state);

	/**
	 * Access control function to determine whether this
	 * transition can be performed on a given transaction
	 *
   * @param AccountInterface $account
	 *
   * @return Boolean
   *   TRUE if access is granted
	 */
  function accessOp(AccountInterface $account);

  /**
   * Do the actual transition on the transaction, and return some html
   * The method in the base class handles the mail notifications
   *
   * @param array $context
   *   the $form_state->values, the plugin 'config', the transation 'old_state'
   *
   * @return array
   *   a renderable array
   *
   * @throws \Exception
   */
  function execute(array $context);

  /**
   * Check whether this transition applicable for the currenct state of the transaction
   *
   * @param AccountInterface $account
   */
  function accessState(AccountInterface $account);

  /**
   * validate transitionSettings and AccessSettings
   *
   * @param type $form
   * @param type $form_state
   *
   * @todo separate validate for each injection access_State, access_settings, and transitionSettings
   */
  static function validateConfigurationForm($form, &$form_state);

  /**
   * Let the plugin tweak the default pluginSettings form
   *
   * @param array $form
   * @param FormStateInterface $form_state
   * @param ImmutableConfig $config
   */
  static function settingsFormTweak(array &$form, FormStateInterface $form_state, ImmutableConfig $config);
}