<?php

/**
 * @file
 *  Contains Drupal\mcapi\OperationBase.
 */
namespace Drupal\mcapi;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\Core\Config\ConfigFactory;
use Drupal\mcapi\OperationInterface;
use Drupal\mcapi\TransactionInterface;
use Drupal\mcapi\CurrencyInterface;

/**
 * Base class for Operations for default methods.
 */
abstract class OperationBase extends ConfigEntityBase implements OperationInterface {

  // these are the settings
  public $id;
  public $config;
  public $label;
  public $weight;
  public $description;

  function __construct($dunno, $op, $definition) {
    // I expected this to happen automatically...
    $this->id = $definition['id'];
    $this->config = \Drupal::config ('mcapi.operation.' . $definition['id']);
    $this->label = $this->config->get ('title') or $this->label = $definition['label'];
    $this->weight = $definition['settings']['weight'];
    $this->description = $definition['description'];
  }

  /**
   * @see \Drupal\mcapi\OperationInterface::opAccess($transaction)
   */
  public function opAccess(TransactionInterface $transaction) {
    $access_plugins = transaction_access_plugins ();
    // the default behaviour is to iterate through the TransactionAccess plugins
    foreach ($transaction->worths[0]as $worth) {
      foreach ($worth->currency->access_operations[$this->id()]as $plugin_id) {
        // Any of the TransactionAccess plugins must return TRUE for BOTH currencies
        // so if any plugin returns TRUE it continues 2 the next currency
        if ($access_plugins[$plugin_id]->checkAccess ($transaction)) continue 2;
      }
      // if none of this currency's plugins returns true then deny access
      return FALSE;
      // right?
    }
    return TRUE;
  }

  /**
   * @see \Drupal\mcapi\OperationInterface::access_form($currency)
   */
  public function access_form(CurrencyInterface $currency) {
    // the operation label and description are already used in the settings group
    $element = array (
      '#title' => $this->label,
      '#description' => $this->description,
      '#type' => 'checkboxes',
      '#options' => transaction_access_plugins(TRUE),
      '#default_value' => array(), // this will be overwritten
      '#weight' => $this->weight
   );
    $op = $this->id();
    if (property_exists ($currency, 'access_operations') && array_key_exists ($op, $currency->access_operations)) {
      $element['#default_value']= $currency->access_operations[$op];
    }
    return $element;
  }

  /**
   * @see \Drupal\mcapi\OperationInterface::settingsForm($form)
   */
  public function settingsForm(array &$form) {
    //gives array keyed page_title, twig, format, button, cancel_button
    module_load_include ('inc', 'mcapi');
    $tokens = implode(', ', mcapi_transaction_list_tokens (FALSE));
    $help = t('Use the following twig tokens: @tokens.', array('@tokens' => $tokens)) .' '.
      l(
        t('What is twig?'),
        'http://twig.sensiolabs.org/doc/templates.html',
        array('external' => TRUE)
      );

    $form['#prefix']= $this->description;
    //careful changing this form because the view operation alters it significantly
    if ($op_title = $this->config->get('op_title')) {
      $form['op_title'] = array (
        '#title' => t ('Link text'),
        '#description' => t ('A one word title for this operation'),
        '#type' => 'textfield',
        '#default_value' => $op_title,
        '#placeholder' => $this->label,
        '#size' => 15,
        '#maxlength' => 15,
        '#weight' => 2,
      );
    }

    $form['sure']= array (
      '#title' => t ('Are you sure page'),
      '#type' => 'fieldset',
      '#weight' => 8
    );

    $form['sure']['page_title'] = array (
      '#title' => t ('Page title'),
      '#description' => t ("Page title for the operation's page") . ' TODO, make this use the serial number and description tokens or twig. Twig would make more sense, in this context.',
      '#type' => 'textfield',
      '#default_value' => $this->config->get('page_title'),
      '#placeholder' => t ('Are you sure?'),
      '#weight' => 4,
      '#required' => TRUE
    );
    $form['sure']['format'] = array (
      '#title' => t ('Transaction display'),
      '#type' => 'radios',
      //TODO might want to get the full list of the transaction entity display modes
      '#options' => array (
        'certificate' => t ('Certificate'),
        'twig' => t ('Custom twig template')
      ),
      '#default_value' => $this->config->get('format'),
      '#required' => TRUE,
      '#weight' => 6
   );
    $form['sure']['twig'] = array (
      '#title' => t ('Template'),
      '#description' => $help,
      '#type' => 'textarea',
      '#default_value' => $this->config->get('twig'),
      '#states' => array (
        'visible' => array (
          ':input[name="format"]' => array (
            'value' => 'twig'
          )
        )
      ),
      '#weight' => 8
   );
    if ($val = $this->config->get('button')) {//because the view operation hasn't saved a value
      $form['sure']['button']= array (
        '#title' => t ('Button text'),
        '#description' => t ('The text that appears on the button'),
        '#type' => 'textfield',
        '#default_value' => $val,
        '#placeholder' => t ("I'm sure!"),
        '#weight' => 10,
        '#size' => 15,
        '#maxlength' => 15,
        '#required' => TRUE
      );
    }
    if ($val = $this->config->get('cancel_button')) {
      $form['sure']['cancel_button']= array (
        '#title' => t ('Cancel button text'),
        '#description' => t ('The text that appears on the cancel button'),
        '#type' => 'textfield',
        '#default_value' => $val,
        '#placeholder' => t ('Cancel'),
        '#weight' => 12,
        '#size' => 15,
        '#maxlength' => 15,
        '#required' => TRUE
      );
    }

    $form['feedback']= array (
      '#title' => t ('Feedback'),
      '#type' => 'fieldset',
      '#weight' => 10
    );
    if ($val = $this->config->get('format2')) {
      $form['feedback']['format2']= array (
        '#title' => t ('Confirm form transaction display'),
        '#type' => 'radios',
        // TODO get a list of the transaction display formats from the entity type
        '#options' => array (
          'certificate' => t ('Certificate'),
          'twig' => t ('Twig template'),
          'redirect' => t ('Redirect to path') ." TODO this isn't working yet"
        ),
        '#default_value' => $val,
        '#required' => TRUE,
        '#weight' => 14
     );
      $form['feedback']['redirect']= array (
        '#title' => t('Redirect path'),
        '#description' => t('Enter a path from the Drupal root, without leading slash.'),
        '#type' => 'textfield',
        '#default_value' => $this->config->get('redirect'),
        '#states' => array (
          'visible' => array (
            ':input[name="format2"]' => array (
              'value' => 'redirect'
            )
          )
        ),
        '#weight' => 16
      );
      $form['feedback']['twig2']= array (
        '#title' => t('Template'),
        '#description' => $help,
        '#type' => 'textarea',
        '#default_value' => $this->config->get('twig2'),
        '#states' => array (
          'visible' => array (
            ':input[name="format2"]' => array (
              'value' => 'twig'
            )
          )
        ),
        '#weight' => 16
      );
      $form['feedback']['message']= array(
        '#title' => t('Success message'),
        '#description' => t('Appears in the message box along with the reloaded transaction certificate'),
        '#type' => 'textfield',
        '#default_value' => $this->config->get('message'),
        '#weight' => 18,
        '#placeholder' => t('The operation was successful')
      );
    }

    if ($val = $this->config->get('send')) {
      $form['notify'] = array(
        '#type' => 'fieldset',
        '#title' => t('Mail the transactees, (but not the current user)'),
        '#weight' => 0
      );
      $form['notify']['send'] = array(
        '#title' => t('Notify both transactees'),
        '#type' => 'checkbox',
        '#default_value' => $val,
        '#weight' =>  0
      );
      $form['notify']['subject'] = array(
        '#title' => t('Mail subject'),
        '#description' => '',
        '#type' => 'textfield',
        '#default_value' => $this->config->get('subject'),
        '#weight' =>  1,
        '#states' => array(
          'visible' => array(
            ':input[name="special[send]"]' => array('checked' => TRUE)
          )
        )
      );
      $form['notify']['body'] = array(
        '#title' => t('Mail body'),
        '#description' => '',
        '#type' => 'textarea',
        '#default_value' => $this->config->get('body'),
        '#weight' => 2,
        '#states' => array(
          'visible' => array(
            ':input[name="special[send]"]' => array('checked' => TRUE)
          )
        )
      );
      $form['notify']['cc'] = array(
        '#title' => t('Carbon copy to'),
        '#description' => 'A valid email address',
        '#type' => 'email',
        '#default_value' => $this->config->get('cc'),
        '#weight' => 3,
        '#states' => array(
          'visible' => array(
            ':input[name="special[send]"]' => array('checked' => TRUE)
          )
        )
      );
    }
  }

  /**
   * @see \Drupal\mcapi\OperationInterface::form($transaction)
   */
  public function form(TransactionInterface $transaction) {
    return array();
  }

  /**
   * @see \Drupal\mcapi\OperationInterface::ajax_submit($form_state_values)
   */
  function ajax_submit(array $form_state_values) {
    $transaction = mcapi_transaction_load ($form_state['values']['serial']);
    $renderable = $this->execute ($form_state['transaction_operation'], $transaction, $form_state['values']);
    // if this is ajax we return the result, otherwise redirect the form
    $commands[]= ajax_command_replace ('#transaction-operation-form', drupal_render ($renderable));
    ajax_deliver (array (
      '#type' => 'ajax',
      '#commands' => $commands
   ));
    exit();
  }

  /**
   * @see \Drupal\mcapi\OperationInterface::execute($transaction, $values)
   */
  public function execute(TransactionInterface $transaction, array $values) {

    drupal_set_message('TODO: finish making the mail work in Operationbase::execute - it might work already!');

    if ($this->config->get('send')) {
      $subject = $this->config->get('subject');
      $body = $this->config->get('body');
      if (!$subject || !$body) continue;

      //here we are just sending one mail at a time, in the recipient's language
      global $language;
      $to = implode(user_load($transaction->payer)->mail, user_load($transaction->payee)->mail);
      $params['transaction'] = $transaction;
      $params['config'] = array(
      	'subject' => $subject,
        'body' => $body,
        'cc' => $this->config->get('cc')
        //bcc is not supported! This is not some cloak and dagger thing!
      );
      drupal_mail('mcapi', 'operation', $to, $language->language, $params);
    }
  }
}

