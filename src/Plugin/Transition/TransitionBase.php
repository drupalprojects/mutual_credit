<?php

/**
 * @file
 *  Contains Drupal\mcapi\Plugin\Transition\TransitionBase.
 */
namespace Drupal\mcapi\Plugin\Transition;

use Drupal\mcapi\Entity\TransactionInterface;
use Drupal\Core\Plugin\PluginBase;

/**
 * Base class for Transitions for default methods.
 */
abstract class TransitionBase extends PluginBase implements TransitionInterface {

  public $label;
  public $description; //

  /**
   *
   * @param array $configuration
   *   the configuration for this transition
   * @param string $plugin_id
   *   the id of this transition
   * @param array $plugin_definition
   *   the definition of this transition
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
  abstract public function opAccess(TransactionInterface $transaction);



  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, array &$form_state) {
    //gives array keyed page_title, twig, format, button, cancel_button
    module_load_include ('inc', 'mcapi');
    $tokens = implode(', ', mcapi_transaction_list_tokens (FALSE));
    $help = t('Use the following twig tokens: @tokens.', array('@tokens' => $tokens)) .' '.
      l(
        t('What is twig?'),
        'http://twig.sensiolabs.org/doc/templates.html',
        array('external' => TRUE)
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
      '#description' => t('A few words suitable for a tooltop'),
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
      '#description' => $help,
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
    $form['sure']['button']= array(
      '#title' => t('Button text'),
      '#description' => t('The text that appears on the button'),
      '#type' => 'textfield',
      '#default_value' => @$this->configuration['button'],
      '#placeholder' => t ("I'm sure!"),
      '#weight' => 10,
      '#size' => 15,
      '#maxlength' => 15,
      '#required' => TRUE
    );

    $form['sure']['cancel_button']= array(
      '#title' => t('Cancel button text'),
      '#description' => t('The text that appears on the cancel button'),
      '#type' => 'textfield',
      '#default_value' => @$this->configuration['cancel_button'],
      '#placeholder' => t('Cancel'),
      '#weight' => 12,
      '#size' => 15,
      '#maxlength' => 15,
      '#required' => TRUE
    );

    $form['feedback']= array(
      '#title' => t('Feedback'),
      '#type' => 'fieldset',
      '#weight' => 6
    );
    $form['feedback']['format2']= array(
      '#title' => t('Confirm form transaction display'),
      '#type' => 'radios',
      // TODO get a list of the transaction display formats from the entity type
      '#options' => array(
        'certificate' => t('Certificate'),
        'twig' => t('Twig template'),
        'redirect' => t('Redirect to path') ." TODO this isn't working yet"
      ),
      '#default_value' => @$this->configuration['format2'],
      '#required' => TRUE,
      '#weight' => 14
   );
    $form['feedback']['redirect'] = array(
      '#title' => t('Redirect path'),
      '#description' => implode(' ', array(
        t('Enter a path from the Drupal root, without leading slash. Use replacements.') . '<br />',
        t('@token for the current user id', array('@token' => '[uid]')),
        t('@token for the current transaction serial', array('@token' => '[serial]'))
      )),
      '#type' => 'textfield',
      '#default_value' => @$this->configuration['redirect'],
      '#states' => array(
        'visible' => array(
          ':input[name="format2"]' => array(
            'value' => 'redirect'
          )
        )
      ),
      '#weight' => 16
    );
    $form['feedback']['twig2']= array(
      '#title' => t('Template'),
      '#description' => $help,
      '#type' => 'textarea',
      '#default_value' => @$this->configuration['twig2'],
      '#states' => array(
        'visible' => array(
          ':input[name="format2"]' => array(
            'value' => 'twig'
          )
        )
      ),
      '#weight' => 16
    );
    $form['feedback']['message']= array(
      '#title' => t('Success message'),
      '#description' => t('Appears in the message box along with the reloaded transaction certificate.') . 'TODO: put help for user and mcapi_transaction tokens, which should be working',
      '#type' => 'textfield',
      '#default_value' => @$this->configuration['message'],
      '#placeholder' => t('The transition was successful'),
      '#weight' => 18
    );
    return $form;
  }


  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, array &$form_state){

  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, array &$form_state){
    //form_state[values] was already cleaned
    $this->setConfiguration($form_state['values']);
  }

  /**
   * this method is for view and create plugins which don't have a form
   * @see \Drupal\mcapi\TransitionInterface::form($transaction)
   */
  public function form(TransactionInterface $transaction) {
    return array();
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
    $this->configuration = $configuration;
  }
  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    //this will prevent the config form showing blanks
    return array(
      'title' => '',
      'tooltop' => '',
      'page_title' => '',
      'format' => '',
      'twig' => '',
      'button' => '',
      'cancel_button' => '',
      'format2' => '',
      'redirect' => '',
      'twig2' => '',
      'message' => ''
    );
  }


  /**
   * {@inheritdoc}
   */
  function ajax_submit(array $form_state_values) {
    $transaction = entity_load('mcapi_transaction', $form_state['values']['serial']);
    $renderable = $this->execute ($form_state['transaction_transition'], $transaction, $form_state['values']);
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
  public function execute(TransactionInterface $transaction, array $context) {

    drupal_set_message('TODO: finish making the mail work in Transitionbase::execute - it might work already!');

    if ($this->configuration['send']) {
      $subject = $this->configuration['subject'];
      $body = $this->configuration['body'];
      if (!$subject || !$body) continue;

      //send one mail at a time, to the owner responsible for each wallet.
      //There is nothing that says the entity owning each wallet has to be connected to a user.
      //Although we could require that all wallet owners themselves should implement ownerInterface
      global $language;

      $params['transaction'] = $transaction;
      $params['config'] = array(
      	'subject' => $subject,
        'body' => $body,
        'cc' => $this->configuration['cc']
        //bcc is not supported! This is not some cloak and dagger thing!
      );
      drupal_mail('mcapi', 'transition', $to, $language->language, $params);
    }
  }

  public function calculateDependencies() {
    return array(
    	'module' => array('mcapi')
    );
  }
}

