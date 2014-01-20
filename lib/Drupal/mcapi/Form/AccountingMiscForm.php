<?php

namespace Drupal\mcapi\Form;

use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Form\ConfigFormBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Field\FieldDefinition;

class AccountingMiscForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('config.context.free')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormID() {
    return 'mcapi_misc_options_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, array &$form_state) {
    $config = $this->configFactory->get('mcapi.misc');

    $form['sentence_template'] = array(
      '#title' => t('Default transaction sentence'),
      '#description' => t('Use the tokens to define how the transaction will read when displayed in sentence mode'),
      '#type' => 'textfield',
      '#default_value' => $config->get('sentence_template'),
      '#weight' => 5
    );

    $form['mix_mode'] = array(
      '#title' => t('Restrict transactions to one currency'),
      '#description' => t('Applies only when more than one currency is available'),
      '#type' => 'checkbox',
      '#default_value' => !$config->get('mix_mode'),
      '#weight' => 7
    );
    $form['child_errors'] = array(
      '#title' => t('Invalid child transactions'),
      '#description' => t('What to do if a child transaction fails validation'),
      '#type' => 'checkboxes',
      '#options' => array(
    	  'mail_user1' => t('Send diagnostics to user 1 by mail'),
        'allow' => t('Allow the parent transaction'),
        'watchdog' => t('Log a warning'),
        'show_messages' => t('Show warning messages to user')
      ),
      '#default_value' => $config->get('child_errors'),
      '#weight' => 7
    );
    $form['worths_delimiter'] = array(
      '#title' => t('Delimiter'),
      '#description' => t('What characters should be used to separate values when a transaction has multiple currencies?'),
      '#type' => 'textfield',
      '#default_value' => $config->get('worths_delimiter'),
      '#weight' => 8,
      '#size' => 10,
      '#maxlength' => 10,
      '#states' => array(
    	  'visible' => array(
    	    ':input[name="mix_mode"]' => array('checked' => FALSE)
        )
      )
    );
    $form['rebuild_mcapi_index'] = array(
      '#title' => t('Rebuild index'),
      '#description' => t('The transaction index table stores the transactions in an alternative format which is helpful for building views'),
      '#type' => 'fieldset',
      '#weight' => 10,
      'button' => array(
        '#type' => 'submit',
        '#value' => 'rebuild_mcapi_index',
      )
    );
    foreach ($this->pluginManager = \Drupal::service('plugin.manager.mcapi.wallet_access')->getDefinitions() as $def) {
      $wallet_access_plugins[$def['id']] = $def['label'];
    }
    $form['wallet_access'] = array(
    	'#title' => t('Default access of users to wallets'),
      '#description' => t('Determine which users can see, pay and pay from wallets by default, and which users can override their own wallets'),
      '#type' => 'details',
      '#tree' => TRUE
    );
    //TODO the following elements might need to be moved to somewhere where they can be re-used by the wallet's own config form.
    $form['wallet_access']['wallet_default_viewers'] = array(
      '#title' => t('Visible to'),
      '#description' => t('Who can see the balance and history of this wallet?'),
      '#type' => 'entity_chooser_selection',
      '#args' => array('user'),
      '#default_value' => $config->get('wallet_default_viewers'),
      '#weight' => 1,
    );
    $form['wallet_access']['wallet_default_payees'] = array(
      '#title' => t('Default payees'),
      '#description' => t('Who can create transactions out of this wallet?'),
      '#type' => 'entity_chooser_selection',
      '#args' => array('user'),
      '#default_value' => $config->get('wallet_default_payees'),
      '#weight' => 2,
    );
    $form['wallet_access']['wallet_default_payers'] = array(
      '#title' => t('Default payers'),
      '#description' => t('Who can create transactions into this wallet?'),
      '#type' => 'entity_chooser_selection',
      '#args' => array('user'),
      '#default_value' => $config->get('wallet_default_payers'),
      '#weight' => 3,
    );
    $form['wallet_short_names'] = array(
      '#title' => t('Wallet short names'),
      '#type' => 'checkbox',
      '#default_value' => !$config->get('wallet_short_names'),
      '#weight' => 7
    );
    $form['wallet_access_personalised'] = array(
      '#title' => t('Personalised wallet access'),
      '#description' => t('Users can adjust these settings, for every wallet'),
      '#type' => 'checkbox',
      '#default_value' => !$config->get('wallet_access_personalised'),
      '#weight' => 7
    );

    return parent::buildForm($form, $form_state);
  }

  public function validateForm(array &$form, array &$form_state) {
    drupal_set_message('check setErrorByName of this form after D8 alpha5', 'warning');return;
    //documentation differs from code in alpha5
    //https://api.drupal.org/api/drupal/core!lib!Drupal!Core!Form!FormBuilder.php/function/FormBuilder%3A%3AsetErrorByName/8
    if (!$form_state['values']['mix_mode'] && empty($form_state['values']['worths_delimiter'])) {
      \Drupal::formBuilder()->setErrorByName('worths_delimiter', $form_state, $this->t('Delimiter is required'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, array &$form_state) {

    $this->configFactory->get('mcapi.misc')
      ->set('sentence_template', $form_state['values']['sentence_template'])
      //careful the mix_mode flag is inverted!!
      ->set('mix_mode', !$form_state['values']['mix_mode'])
      ->set('worths_delimiter', $form_state['values']['worths_delimiter'])
      ->set('child_errors', $form_state['values']['child_errors'])
      ->set('wallet_access', $form_state['values']['default_wallet_access'])
      ->set('wallet_short_names', $form_state['values']['wallet_short_names'])
      ->set('wallet_access_personalised', $form_state['values']['wallet_access_personalised'])
      ->save();

    parent::submitForm($form, $form_state);

    if($form_state['triggering_element']['#value'] == 'rebuild_mcapi_index') {
      //not sure where to put this function
       \Drupal::entityManager()->getStorageController('mcapi_transaction')->indexRebuild();
       drupal_set_message("Index table is rebuilt");
       $form_state['redirect'] = 'admin/reports/status';
    }
  }
}


