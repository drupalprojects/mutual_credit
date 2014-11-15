<?php

/**
 * @file
 *  Contains Drupal\mcapi\Plugin\Transition2step.
 */
namespace Drupal\mcapi\Plugin;

use Drupal\mcapi\TransactionInterface;
use Drupal\mcapi\Entity\Transaction;
use Drupal\Core\Form\FormStateInterface;
use Drupal\mcapi\Entity\State;

/**
 * Base class for Transitions for default methods.
 */
abstract class Transition2step extends TransitionBase {

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);

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


    $form['access'] = array(
      '#title' => t('Access control'),
      '#description' => t('Who can @label transactions in each state?', array('@label' => $this->label)),
      '#type' => 'details',
      '#tree' => TRUE,
      '#collapsible' => TRUE,
      '#open' => FALSE,
      '#weight' => 8,
    );

    //TODO would be really nice if this was in a grid
    foreach (State::loadMultiple() as $state) {
      $form['access'][$state->id] = array (
        '#title' => $state->label,
        '#description' => $state->description,
        '#type' => 'checkboxes',
        '#options' => array(
          'payer' => t('Owner of payer wallet'),
          'payee' => t('Owner of payee wallet'),
          'creator' => t('Creator of the transaction'),
          'helper' => t('An exchange helper'),
          'admin' => t('The super admin')
          //its not elegant for other modules to add options
        ),
        '#default_value' => $this->configuration['access'][$state->id],
        '#weight' => $this->configuration['weight']
      );
    }

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
      '#description' => $this->help,
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
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return array_merge(
      parent::defaultConfiguration(),
      array(
        'button' => '',
        'cancel_button' => '',
        'access' => '',
        'format2' => '',
        'redirect' => '',
        'twig2' => '',
        'message' => ''
      )
    );
  }


  /**
   * {@inheritdoc}
   */
  function ajax_submit(array $form_state_values) {
    $values = $form_state->getValues();
    $transaction = Transaction::load($values['serial']);
    $renderable = $this->execute($form_state->get('transaction_transition'), $transaction, $form_state['values']);
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
    $state_id = $transaction->state->target_id;
    $account = \Drupal::currentUser();
    foreach (@$options[$state_id] as $option) {
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

  /**
   * {@inheritdoc}
   */
  abstract public function execute(TransactionInterface $transaction, array $context);

}

