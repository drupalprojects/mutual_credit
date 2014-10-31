<?php

/**
 * @file
 *  Contains Drupal\mcapi\Plugin\TransitionBase.
 */
namespace Drupal\mcapi\Plugin;

use Drupal\mcapi\TransactionInterface;
use Drupal\Core\Plugin\PluginBase;
use Drupal\Core\Url;
use Drupal\mcapi\Entity\Transaction;
use Drupal\Core\Form\FormStateInterface;

/**
 * Base class for Transitions for default methods.
 */
abstract class TransitionBase extends PluginBase implements TransitionInterface {

  public $label;
  public $description;

  /**
   * {@inheritdoc}
   */
  function __construct(array $configuration, $plugin_id, array $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->configuration += $this->defaultConfiguration();
    $this->label = $this->configuration['title'] ? : $this->pluginDefinition['label'];
    $this->description = @$this->configuration['tooltip'] ? : $this->pluginDefinition['description'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    //gives array keyed page_title, twig, format, button, cancel_button
    module_load_include ('inc', 'mcapi');
    $tokens = implode(', ', mcapi_transaction_list_tokens (FALSE));
    //TODO currently there is NO WAY to put html in descriptions because twig autoescapes it
    //see cached classes extending TwigTemplate->doDisplay twig_drupal_escape_filter last argument
    $this->help = t('Use the following twig tokens: @tokens.', array('@tokens' => $tokens)) .' '.
      \Drupal::l(
        $this->t('What is twig?'),
        Url::fromUri('http://twig.sensiolabs.org/doc/templates.html')
      );
    //careful changing this form because the view transition alters it significantly
    $form['title'] = array(
      '#title' => t('Link text'),
      '#description' => t('A one word title for this transition'),
      '#type' => 'textfield',
      '#default_value' => $this->label,
      '#placeholder' => $this->pluginDefinition['label'],
      '#size' => 15,
      '#maxlength' => 15,
      '#weight' => 1,
    );
    $form['tooltip'] = array(
      '#title' => t('Short description'),
      '#description' => t('A few words suitable for a tooltip'),
      '#type' => 'textfield',
      '#default_value' => @$this->configuration['tooltip'],
      '#placeholder' => $this->pluginDefinition['description'],
      '#size' => 64,
      '#maxlength' => 128,
      '#weight' => 2,
    );

    $form['sure']= array(
      '#title' => t('Are you sure page'),
      '#type' => 'fieldset',
      '#weight' => 3
    );

    $form['sure']['page_title'] = array(
      '#title' => t('Page title'),
      '#description' => t ("Page title for the transition's page") . ' TODO, make this use the serial number and description tokens or twig. Twig would make more sense, in this context.',
      '#type' => 'textfield',
      '#default_value' => $this->configuration['page_title'],
      '#placeholder' => t('Are you sure?'),
      '#weight' => 4,
      '#required' => TRUE
    );
    $form['sure']['format'] = array(
      '#title' => t('Transaction display'),
      '#type' => 'radios',
      //TODO might want to get the full list of the transaction entity display modes
      '#options' => array(
        'certificate' => t('Certificate (can be themed per-currency)'),
        'twig' => t('Custom twig template')
      ),
      '#default_value' => $this->configuration['format'],
      '#required' => TRUE,
      '#weight' => 6
    );
    $form['sure']['twig'] = array(
      '#title' => t('Template'),
      '#description' => $this->help,//TODO this is escaped in twig so links don't work
      '#type' => 'textarea',
      '#default_value' => @$this->configuration['twig'],
      '#states' => array(
        'visible' => array(
          ':input[name="format"]' => array(
            'value' => 'twig'
          )
        )
      ),
      '#weight' => 8
    );
    return $form;
  }


  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state){
    //this is required by the interface
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state){
    //form_state->values was already cleaned
    $this->setConfiguration($form_state->getValues());
  }

  /**
   * this method is for plugins which don't have a form, like view and create
   * @see \Drupal\mcapi\TransitionInterface::form($transaction)
   */
  public function form(TransactionInterface $transaction) {
    return array();
  }

  /**
   * {@inheritdoc}
   */
  public function getConfiguration($key = NULL) {
    if ($key) {
      return @$this->configuration[$key];
    }
    return $this->configuration;
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
    //this will prevent the config form showing blanks
    return array(
      'title' => '',
      'tooltip' => '',
      'page_title' => '',
      'format' => '',
      'twig' => '',
    );
  }


  /**
   * {@inheritdoc}
   */
  function ajax_submit(array $form_state_values) {
    $transaction = Transaction::load($form_state_values['serial']);
    $renderable = $this->execute($form_state->get('transaction_transition'), $transaction, $form_state_values);
    // if this is ajax we return the result, otherwise redirect the form
    $commands[]= ajax_command_replace ('#transaction-transition-form', drupal_render ($renderable));
    ajax_deliver (array(
      '#type' => 'ajax',
      '#commands' => $commands
   ));
    exit();
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() {
    return array(
      'module' => array('mcapi')
    );
  }



  /**
   *  access callback for transaction transition 'view'
   *  @return boolean
  */
  public function opAccess(TransactionInterface $transaction) {
    $options = array_filter($this->configuration['access']);
    if (!array_key_exists($transaction->state->target_id, $options)) {
      drupal_set_message(
        t(
          "Please resave the @label transition, paying attention to access control for the unconfigured new '@statename' state:",
          array(
            '@statename' => $transaction->state->target_id,
            '@label' => $this->label
          )
        ) . ' '.l('admin/accounting/workflow/undo', 'admin/accounting/workflow/undo'),
        'warning',
        FALSE
      );
    }
    $account = \Drupal::currentUser();
    foreach ($options[$transaction->state->target_id] as $option) {
      switch ($option) {
      	case 'helper':
      	  if ($account->hasPermission('exchange helper')) return TRUE;
      	  continue;
      	case 'admin':
      	  if ($account->hasPermission('manage mcapi')) return TRUE;
      	  continue;
      	case 'payer':
      	case 'payee':
      	  $wallet = $transaction->{$option}->entity;
      	  $parent = $$wallet->getOwner();
      	  if ($parent && $wallet->pid->value == $account->id() && $parent->getEntityTypeId() == 'user') {
      	    return TRUE;
      	  }
      	  continue;
      	case 'creator':
      	  if ($transaction->creator->target_id == $account->id()) return TRUE;
      	  continue;
      }
    }

    return FALSE;
  }

}

