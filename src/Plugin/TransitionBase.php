<?php

/**
 * @file
 * Contains Drupal\mcapi\Plugin\TransitionBase.
 */
namespace Drupal\mcapi\Plugin;

use Drupal\mcapi\TransactionInterface;
use Drupal\mcapi\McapiEvents;
use Drupal\mcapi\TransactionSaveEvents;
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
  private $eventDispatcher;
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
    $this->eventDispatcher = \Drupal::service('event_dispatcher');
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

  static function transitionSettings(array $form, FormStateInterface $form_state, ImmutableConfig $config) {
    return [];
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
   * The default plugin access allows selection of transaction relatives.
   *
   * @param array $element
   */
  static function accessSettingsForm(&$element, $default) {
    $element['access'] = [
      '#type' => 'checkboxes',
      '#options' => \Drupal::service('mcapi.transaction_relative_manager')->options(),
      '#default_value' => $default,
    ];
  }

  /**
   *
   * {@inheritDoc}
   */
  static function validateConfigurationForm($form, &$form_state) {
    //@todo validate the access checkboxes if we could be bothered
  }

  /**
   * default access callback for transaction transition
   * uses transaction relatives
   *
   * @return boolean
   */
  public function accessOp(AccountInterface $account) {
    //children can't be edited that would be too messy
    if ($this->transaction->parent->value) {
      return FALSE;
    }
    foreach (array_filter($this->configuration['access']) as $relative) {
      //$check if the $acocunt is this relative to the transaction
      $relative = $this->relatives[$relative];
      if ($relative->isRelative($this->transaction, $account)) {
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
   * Perform a transition on the transaction and save it.
   *
   * @param array $values
   *   the form state values from the transition form
   *
   * @return array
   *   a renderable array
   *
   * @todo make a drush command for doing transitions
   * E.g. drush mcapi-transition [serial] edit -v description blabla
   */
  protected function baseExecute(array $values) {

    $context = [
      'values' => $values,
      'old_state' => $this->transaction->state->value,
    ];

    $context['transition'] = $this;
    //notify other modules, especially rules.
    //the moduleHandler isn't loaded because__construct didn't run in beta10
    /*
    $renderable = $this->moduleHandler->invokeAll(
      'mcapi_transition',
      [$this->transaction, $context]
    );*/

    //@todo, need to get an $output from event handlers i.e. hooks
    //namely more $renderable items?
    $this->eventDispatcher->dispatch(
      McapiEvents::TRANSITION,
      new TransactionSaveEvents(clone($this->transaction), $context)
    );

    $this->transaction->save();

    if(!$renderable) {
      $renderable = ['#markup' => 'transition returned nothing renderable'];
    }
    return $renderable;
  }

  static function settingsFormTweak(array &$form, FormStateInterface $form_state, ImmutableConfig $config) {}

  /**
   * {@inheritDoc}
   */
  function validateForm(array $form, FormStateInterface $form_state) {}



}

