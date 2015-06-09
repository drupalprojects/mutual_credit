<?php

/**
 * @file
 * Contains Drupal\mcapi\Plugin\TransitionBase.
 */
namespace Drupal\mcapi\Plugin;

use Drupal\mcapi\TransactionInterface;
use Drupal\Core\Plugin\PluginBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Component\Plugin\ConfigurablePluginInterface;
use Drupal\Core\Config\ImmutableConfig;

/**
 * Base class for Transitions for default methods.
 */
abstract class TransitionBase extends PluginBase implements TransitionInterface {

  private $relatives;
  protected $transaction;
  protected $entityFormBuilder;
  protected $moduleHandler;

  /**
   * {@inheritdoc}
   */
  function __construct(array $configuration, $plugin_id, array $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->configuration += $this->defaultConfiguration();
    //can't work out how to inject these
    $this->entityFormBuilder = \Drupal::service('entity.form_builder');
    $this->moduleHandler = \Drupal::service('module_handler');
    $this->relatives = \Drupal::service('mcapi.transaction_relative_manager')->active();
  }

  function setTransaction(TransactionInterface $transaction) {
    $this->transaction = $transaction;
  }
  /**
   *
   * @return TransactionInterface
   * @throws \Exception
   */
  function getTransaction() {
    if ($this->transaction) {
      return $this->transaction;
    }
    throw new \Exception ('Transition plugin not properly initiated. Transaction not set');
  }

  /**
   * {@inheritdoc}
   */
  public function form(array &$form) {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getConfiguration($key = NULL) {
    return $key && array_key_exists($key, $this->configuration) ?
      $this->configuration[$key] :
      $this->configuration;
  }

  /**
   * {@inheritdoc}
   */
  public function setConfiguration(array $configuration) {
    $this->configuration = $configuration;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'title' => '',
      'tooltip' => '',
      'states' => [],
      'page_title' => '',
      'format' => '',
      'twig' => '',
      'display' => '',
      'button' => '',
      'cancel_button' => '',
      'redirect' => '',
      'message' => '',
      'weight' => 0,
      'access' => '',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function accessState(AccountInterface $account) {
    return isset($this->configuration['states'][$this->transaction->state->target_id]);
  }

  /**
   * {@inheritdoc}
   */
  public static function accessSettingsElement(&$element, $default) {
    $element['access'] = [
      '#type' => 'checkboxes',
      '#options' => \Drupal::service('mcapi.transaction_relative_manager')->options(),
      '#default_value' => $default,
    ];
  }

  /**
   * default access callback for transaction transition
   * uses transaction relatives
   *
   * @return boolean
   */
  public function accessOp(AccountInterface $account, $arg2=null) {
    if ($arg2)die('accessOp has too many args in '.$this->getPluginId());
    //children can't be edited that would be too messy
    if ($this->transaction->parent->value) {
      return FALSE;
    }
    foreach (array_filter($this->configuration['access']) as $relative) {
      //$check if the $acocunt is this relative to the transaction
      $plugin = $this->relatives[$relative];
      if ($plugin->isRelative($this->transaction, $account)) {
        return TRUE;
      }
    }
    return FALSE;
  }

  function calculateDependencies() {
    return [
      'module' => ['mcapi']
    ];
  }

  /**
   * {@inheritDoc}
   */
  static function settingsFormTweak(array &$form, FormStateInterface $form_state, ImmutableConfig $config) {
    //don't forget to validate
  }

  /**
   * {@inheritDoc}
   */
  public static function validateSettingsForm(array $form, FormStateInterface $form_state) {
    //@todo validate the access checkboxes if we could be bothered
  }


  /**
   * {@inheritDoc}
   */
  function validateForm(array $form, FormStateInterface $form_state) {

  }

}

