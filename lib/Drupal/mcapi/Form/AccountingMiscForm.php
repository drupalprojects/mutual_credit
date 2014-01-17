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
    foreach ($this->pluginManager = \Drupal::service('mcapi.wallet.manager')->getDefinitions() as $def) {
      $wallet_access_plugins[$def['id']] = $def['label'];
    }
    $form['default_wallet_access'] = array(
      '#title' => t('Default wallet access setting'),
      //we could use entity_chooser here...
      '#type' => 'select',
      '#options' => $wallet_access_plugins,
      '#default_value' => $config->get('default_wallet_access'),
      '#weight' => 12,
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
      ->set('default_wallet_access', $form_state['values']['default_wallet_access'])
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


