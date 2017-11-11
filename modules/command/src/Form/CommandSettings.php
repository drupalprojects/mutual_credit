<?php

namespace Drupal\mcapi_command\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 *
 */
class CommandSettings extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormID() {
    return 'mcapi_command_settings_form';
  }

  /**
   *
   */
  public function buildform(array $form, FormStateInterface $form_state) {
    $tokens = array('[transaction:payer] OR [transaction:payee]', '[transaction:quantity]', '[transaction:description]');
    $config = $this->configFactory->get('mcapi_command.settings');
    $form['requests'] = array(
      '#title' => t('Incoming messages from phones'),
      '#description' => t('Define the form of the text messages.') . ' ' .
      t("Try to include variations and get feedback from your users as to what works.") . ' ' .
      t("Use the following tokens:") . ' ' .
      theme('item_list', array('items' => $tokens)) . ' ',
      '#type' => 'fieldset',
      '#weight' => -2,
    );
    // These variable names double up as the callback functions.
    $form['requests']['command_strings'] = array(
      '#title' => t('Expressions for recording a transfer'),
      '#description' => implode(' ', array(
        t('One per line.'),
        t('Case insensitive.'),
        t('Example: pay johnsmith 14 for gardening'),
      )),
      '#type' => 'textarea',
      '#rows' => 3,
      '#element_validate' => array(
        array(get_class($this), 'validate_commands_syntax'),
      ),
      '#default_value' => $config->get('command_strings', ''),
    );
    // @todo multiple options would be possible.
    $form['requests']['match_fields'] = array(
      '#title' => t('Match wallets to'),
      '#description' => 'How will wallets be identified in command strings?',
      '#type' => 'radios',
      '#options' => array(
        'w.wid' => t('Wallet ID number'),
      ),
      '#default_value' => $config->get('match_fields', ''),
      '#required' => TRUE,
    );
    if (\Drupal::config()->get('mcapi.settings')->get('wallet_unique_name')) {
      $form['requests']['match_fields']['#options']['w.name'] = t('Unique wallet name');
    }
    if (\Drupal::config()->get('mcapi.settings')->get('wallet_one')) {
      $form['requests']['match_fields']['#options']['holder'] = t("Wallet holder's name");
    }

    $form['curr_id'] = array(
      '#title' => t('Currency'),
      '#description' => t('Currently the commands will only work with one currency, in order to keep the user interface simple.'),
      '#type' => 'mcapi_currencies',
      '#default_value' => $config->get('curr_id'),
    );

    $form['responses'] = array(
      '#title' => t('Responses'),
      '#type' => 'fieldset',
      '#weight' => -2,
    );
    $form['responses']['response_success'] = array(
      '#title' => t('Response for a successful exchange'),
      '#description' => t("Leave blank for no response, or put [inherit] to show default messages"),
      '#type' => 'textfield',
      '#default_value' => $config->get('response_success', ''),
      '#weight' => -1,
    );
    $form['responses']['syntax_error'] = array(
      '#title' => t('Error response'),
      '#description' => t("Response in case the incoming message cannot be parsed"),
      '#type' => 'textfield',
      '#default_value' => $config->get('syntax_error', ''),
      '#weight' => 0,
    );
    $form['responses']['twitter_response'] = array(
      '#title' => t('Twitter success response'),
      '#description' => t("Assumes the tweeter is following this system's twitter account.") . ' ' . t('Leave blank for no response'),
      '#type' => 'textfield',
      '#default_value' => $config->get('twitter_response', ''),
      '#weight' => 0,
    );
    if (\Drupal::moduleHandler()->moduleExists('twitter')) {
      // Option for sending a return tweet: mcapi_command_twitter_response.
    }
    $form['submit'] = array('#type' => 'submit', '#value' => t('Submit'));
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state, $op = NULL) {
    // Do we need to clean form_state['values']?
    $config = $this->configFactory->getEditable('mcapi_command.settings');
    foreach ($form_state->getValues() as $key => $val) {
      $config->set($key, $val);
    }
    $config->save();

    parent::submitForm($form, $form_state);
  }

  /**
   * Element validate callback
   * ensures that command syntax contains the critical tokens.
   */
  function validate_commands_syntax(&$element, FormStateInterface $form_state) {
    $templates = explode("\n", $element['#value']);
    foreach ($templates as $template) {
      // Check it has quantity in it.
      if (strpos($template, '[transaction:quantity]') === FALSE) {
        form_error($element, t("Each expression should include '@token' : @expression", array('@expression' => $template, '@token' => '[transaction:quantity]')));
      }
      $payer = strpos($template, '[transaction:payer]');
      $payee = strpos($template, '[transaction:payee]');
      // Of $payer and $payee, one should be FALSE and one should be an integer.
      $integer = $payer === FALSE ? $payee : $payer;
      if (!is_integer($integer)) {
        form_error($element, t("'@template' should include EITHER [transaction:payee] OR [transaction:payer]", array('@template' => $template)));
      }
    }
  }

  function getEditableConfigNames() {
    return ['mcapi_command.settings'];
  }

}
