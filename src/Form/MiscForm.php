<?php

namespace Drupal\mcapi\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\mcapi\Mcapi;

class MiscForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormID() {
    return 'mcapi_misc_options_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->configFactory->get('mcapi.misc');
    module_load_include('inc', 'mcapi');
    foreach (Mcapi::transactionTokens(TRUE) as $token) {
      $tokens[] = "[mcapi:$token]";
    }
    $form['sentence_template'] = array(
      '#title' => t('Default transaction sentence'),
      '#description' => t('Use the following tokens to define how the transaction will read when displayed in sentence mode: @tokens', array('@tokens' => implode(', ', $tokens))),
      '#type' => 'textfield',
      '#default_value' => $config->get('sentence_template'),
      '#weight' => 2
    );
    $form['ticks_name'] = array(
      '#title' => t('Base Unit'),
      '#description' => t('Plural name of the base unit, used for intertrading.'),
      '#type' => 'textfield',
      '#default_value' => $config->get('ticks_name'),
      '#weight' => 7
    );
    $menu_options = \Drupal::moduleHandler()->moduleExists('menu')
      ? menu_get_menus()
      : menu_list_system_menus();
    $form['exchange_menu'] = array(
      '#title' => t('Menu'),
      '#description' => t("Menu containing dynamic menu links to user's exchange(s)"),
      '#type' => 'select',
      '#options' => $menu_options,
      '#default_value' => $config->get('exchange_menu'),
      '#weight' => 8
    );
    $form['child_errors'] = array(
      '#title' => t('Invalid child transactions'),
      '#description' => t('What to do if a child transaction fails validation'),
      '#type' => 'checkboxes',
      '#options' => array(
    	  'mail_user1' => t('Send diagnostics to user 1 by mail'),
        'allow' => t('Allow the parent transaction'),
        'log' => t('Log a warning'),
        'show_messages' => t('Show warning messages to user')
      ),
      '#default_value' => $config->get('child_errors'),
      '#weight' => 10
    );
    $form['worths_delimiter'] = array(
      '#title' => t('Delimiter'),
      '#description' => t('What characters should be used to separate values when a transaction has multiple currencies?'),
      '#type' => 'textfield',
      '#default_value' => $config->get('worths_delimiter'),
      '#weight' => 12,
      '#size' => 10,
      '#maxlength' => 10,
    );
    $form['zero_snippet'] = array(
      '#title' => t('Zero snippet'),
      '#description' => t("string to replace '0:00' when the currency allows zero transactions"),
      '#type' => 'textfield',
      '#default_value' => $config->get('zero_snippet'),
      '#weight' => 13,
      '#size' => 20,
      '#maxlength' => 128,
    );
    //NB Instead of this, 'counted' could be a property of each transaction state
    //however at the moment that would involve user 1 editing the yaml files
    //because transaction states have no ui to edit them
    $form['counted'] = array(
    	'#title' => t('Counted transaction states'),
      '#description' => t("Transactions in these states will comprise the wallet's balance"),
      '#type' => 'checkboxes',
      '#options' => mcapi_entity_label_list('mcapi_state'),
      '#default_value' => $config->get('counted'),
      '#weight' => 14,
      TRANSACTION_STATE_FINISHED => array(
    	  '#disabled' => TRUE,
        '#value' => TRUE,
      ),
      TRANSACTION_STATE_ERASED => array(
    	  '#disabled' => TRUE,
        '#value' => FALSE,
      )
    );
    //TODO only show this button if transactions are present
    $form['rebuild_mcapi_index'] = array(
      '#title' => t('Rebuild index'),
      '#description' => t('The transaction index table stores the transactions in an alternative format which is helpful for building views'),
      '#type' => 'fieldset',
      '#weight' => 15,
      'button' => array(
        '#type' => 'submit',
        '#value' => 'rebuild_mcapi_index',
        '#submit' => array(
          array(get_class($this), 'rebuild_mcapi_index')
        )
      )
    );
    return parent::buildForm($form, $form_state);
  }

  public function validateForm(array &$form, FormStateInterface $form_state) {

  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $config = $this->configFactory->get('mcapi.misc');

    $config
      ->set('sentence_template', $values['sentence_template'])
      //careful the mix_mode flag is inverted!!
      ->set('exchange_menu', !$values['exchange_menu'])
      ->set('ticks_name', $values['ticks_name'])
      ->set('zero_snippet', $values['zero_snippet'])
      ->set('worths_delimiter', $values['worths_delimiter'])
      ->set('child_errors', $values['child_errors'])
      ->set('counted', $values['counted'])
      ->save();

    parent::submitForm($form, $form_state);
    if($config->get('counted') != $values['counted']) {
      rebuild_mcapi_index($form, $form_state);
    }
  }

  static function rebuild_mcapi_index(array &$form, FormStateInterface $form_state) {
    //not sure where to put this function
    \Drupal::entityManager()->getStorage('mcapi_transaction')->indexRebuild();
    drupal_set_message("Index table is rebuilt");
    $form_state->setRedirect('system.status');
  }

}


