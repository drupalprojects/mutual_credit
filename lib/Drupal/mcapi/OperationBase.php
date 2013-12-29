<?php

/**
 * @file
 *  Contains Drupal\mcapi\CurrencyTypeBase.
 */

namespace Drupal\mcapi;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\Core\Config\ConfigFactory;
use Drupal\mcapi\OperationInterface;
use Drupal\mcapi\TransactionInterface;
use Drupal\mcapi\CurrencyInterface;
use Drupal\mcapi\OperationBase;

/**
 * Base class for Operations for default methods.
 */
abstract class OperationBase extends ConfigEntityBase implements OperationInterface {

  //these are the settings
  public $id;
  public $config;
  public $label;
  public $weight;
  public $description;

  function __construct($dunno, $op, $definition) {
    //I expected this to happen automatically...
    $this->id = $definition['id'];
    $this->config = \Drupal::config('mcapi.operation.'.$definition['id']);
    $this->label = $this->config->get('title') or $this->label = $definition['label'];
    $this->weight = $definition['settings']['weight'];
    $this->description = $definition['description'];
  }

  /*
   * this rather assumes that the above function is used
  */
  public function opAccess(TransactionInterface $transaction) {
    $access_plugins = transaction_access_plugins(TRUE);
    //the default behaviour is to iterate through the TransactionAccess plugins
      foreach ($transaction->worths[0] as $worth) {
        foreach ($worth->currency->access_operations[$this->id()] as $plugin_id) {
          //Any of the TransactionAccess plugins must return TRUE for BOTH currencies
          //so if any plugin returns TRUE it continues 2 the next currency
          if ($access_plugins[$plugin_id]->checkAccess($transaction)) continue 2;
        }
        //if none of this currency's plugins returns true then deny access
        return FALSE;
        //right?
      }
      return TRUE;
  }

  /*
   * inject something into the confirm_form
   */
  public function operation_form(TransactionInterface $transaction) {
    return array();
  }


  /**
   * default form for configuring access to an operations for a currency
   * offers a checkbox list of the transaction_operation_access callbacks
   */
  public function access_form(CurrencyInterface $currency) {
    //the operation label and description are already used in the settings group
    $element = array(
      '#title' => $this->label,
      '#description' => $this->description,
      '#type' => 'checkboxes',
      '#options' => transaction_access_plugins(FALSE),
      '#default_value' => array(),//this will be overwritten
      '#weight' => $this->weight,
    );
    $op = $this->id();
    if (property_exists($currency, 'access_operations') && array_key_exists($op, $currency->access_operations)) {
      $element['#default_value'] = $currency->access_operations[$op];
    }
    return $element;
  }


/*
 * basic settings form which individual operations can alter
 */
  public function settingsForm(array &$form, ConfigFactory $config) {
    $conf = $config->get('general');

    $form['#prefix'] = $this->description;
    $form['general'] = array(
      '#title' => t('Basic settings'),
      '#description' => t('These settings are common to all operations'),
      '#type' => 'fieldset',
      '#weight' => 10
    );
    $form['general']['op_title'] = array(
      '#title' => t('Link text'),
      '#description' => t('A one word title for this operation'),
      '#type' => 'textfield',
      '#default_value' => $conf['op_title'],
      '#placeholder' => $this->label,
      '#size' => 15,
      '#maxlength' => 15,
      '#weight' =>  2,
      '#required' => TRUE,
    );

    $form['general']['page_title'] = array(
      '#title' => t('Page title'),
      '#description' => t('Page title for the confirm form'),
      '#type' => 'textfield',
      '#default_value' => $conf['page_title'],
      '#placeholder' => t('Are you sure?'),
      '#weight' =>  4,
      '#required' => TRUE,
    );
    $form['general']['format'] = array(
      '#title' => t('Confirm form transaction display'),
      '#type' => 'radios',
      //TODO get a list of the transaction display formats from the entity type
      '#options' => array(
        'certificate' => t('Certificate'),
        'twig' => t('Twig template'),
        //'tokens' => t('Drupal token system'),
      ),
      '#default_value' => $conf['format'],
       '#required' => TRUE,
      '#weight' =>  6
    );
    $form['general']['twig'] = array(
      '#title' => t('Template'),
      '#description' => '//TODO list the twig variables here',
      '#type' => 'textarea',
      '#default_value' => $conf['twig'],
      '#states' =>array(
        'visible' => array(
          ':input[name="general[format]"]' => array('value' => 'twig')
        )
      ),
      '#weight' =>  8
    );
    $form['general']['button'] = array(
      '#title' => t('Button text'),
      '#description' => t('The text that appears on the button'),
      '#type' => 'textfield',
      '#default_value' => $conf['button'],
      '#placeholder' => t("I'm sure!"),
      '#weight' => 10,
      '#size' => 15,
      '#maxlength' => 15,
      '#required' => TRUE,
    );
    $form['general']['cancel_button'] = array(
      '#title' => t('Cancel button text'),
      '#description' => t('The text that appears on the cancel button'),
      '#type' => 'textfield',
      '#default_value' => $conf['cancel_button'],
      '#placeholder' => t('Cancel'),
      '#weight' =>  12,
      '#size' => 15,
      '#maxlength' => 15,
      '#required' => TRUE,
    );
    $form['general']['format2'] = array(
      '#title' => t('Confirm form transaction display'),
      '#type' => 'radios',
      //TODO get a list of the transaction display formats from the entity type
      '#options' => array(
        'certificate' => t('Certificate'),
        'twig' => t('Twig template'),
        //'tokens' => t('Drupal token system'),
      ),
      '#default_value' => $conf['format2'],
      '#required' => TRUE,
      '#weight' =>  14
    );
    $form['general']['twig2'] = array(
      '#title' => t('Template'),
      '#description' => '//TODO list the twig variables here',
      '#type' => 'textarea',
      '#default_value' => $conf['twig2'],
      '#states' =>array(
        'visible' => array(
          ':input[name="general[format2]"]' => array('value' => 'twig')
        )
      ),
      '#weight' =>  16
    );
    $form['general']['message'] = array(
      '#title' => t('Success message'),
      '#description' => t('Appears in the message box along with the reloaded transaction certificate'),
      '#type' => 'textfield',
      '#default_value' => $conf['message'],
      '#weight' =>  18,
      '#placeholder' => t('The operation was successful')
    );
  }

  /*
   * default form callback for all transaction operations
  */
  public function confirm_form(array $form, array &$form_state, $op) {
    //TODO ENSURE THE FORM ID IS SET TO TRANSACTION_OPERATION_FORM
    //TODO make this work
    $form['serial'] = array(
      '#type' => 'value',
      '#value' => $transaction->serial
    );
    $form_state['transaction_operation'] = $this->id();

    //TODO later
    if (array_key_exists('form callback', $info) && function_exists($info['form callback'])) {
      $form += $info['form callback']($op, $transaction);
    }

    if ($this->twig) {

    }
    else {
       $form['certificate'] = transaction_view($transaction, 'certificate', TRUE);
    }

    //extend ConfirmFormBase instead of this
    $form = confirm_form(
      $form,
      $transaction->label(),
      empty($this->redirect) ? $transaction->uri() : $this->redirect,
      $this->sure_message,
      $this->label(),
      t('Back'),
      $this->id()
    );
    $form['#submit'][] = array($this, 'execute');
    return $form;
  }

  //this is only called from the are you sure form, and always from ajax
  function ajax_submit(array $form_state_values) {
    $transaction = mcapi_transaction_load($form_state['values']['serial']);
    $renderable = $this->execute(
      $form_state['transaction_operation'],
      $transaction,
      $form_state['values']
    );
    //if this is ajax we return the result, otherwise redirect the form
    $commands[] = ajax_command_replace('#transaction-operation-form', drupal_render($renderable));
    ajax_deliver(array(
    '#type' => 'ajax',
    '#commands' => $commands
    ));
    exit();
  }


  abstract function execute(TransactionInterface $transaction, array $values);

}

