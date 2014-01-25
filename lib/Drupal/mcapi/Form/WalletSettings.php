<?php

namespace Drupal\mcapi\Form;

use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Form\ConfigFormBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Field\FieldDefinition;

class WalletSettings extends ConfigFormBase {

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
    return 'mcapi_wallet_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, array &$form_state) {
    $config = $this->configFactory->get('mcapi.wallets');
    //A wallet can be attached to any entity with an entity reference field pointing towards the exchange entity
    //OR to an exchange entity itself
    //@todo this could work for bundles or different user types or roles - many richer ways to do it
    $form['entity_types'] = array(
    	'#title' => t('Entity types'),
    	'#description' => t('Put the maximum number of wallets that entities of each type can hold.') .' '.
        t('Only bundles with entity reference fields to Exchanges are shown.'),
      '#type' => 'details',
      '#tree' => TRUE
    );
    module_load_include('inc', 'mcapi');
    foreach (get_exchange_entity_fieldnames() as $entity_type => $fieldname) {
      $definition = entity_get_info($entity_type);
      $form['entity_types'][$definition['id']] = array(
      	'#title' => $definition['label'],//don't know if this is translated or translatable!
        '#type' => 'number',
        '#min' => 0,
        '#default_value' => $config->get('entity_types.'.$definition['id']),
        '#size' => 2,
        '#max_length' => 2
      );
    }
    $form['unique_names'] = array(
      '#title' => t('Unique wallet names'),
      '#description' => t('Every wallet name on the system must be unique.'),
      '#type' => 'checkbox',
      '#default_value' => !$config->get('unique_names'),
      '#weight' => 2
    );
    $form['autoadd_name'] = array(
      '#title' => t('Name of auto-added wallet'),
      '#description' => t('Name of new wallet created automatically for each new user, or leave blank not to create.'),
      '#type' => 'textfield',
      '#placeholder' => t('My wallet'),
      '#default_value' => !$config->get('autoadd_name'),
      '#weight' => 3
    );

    foreach ($this->pluginManager = \Drupal::service('plugin.manager.mcapi.wallet_access')->getDefinitions() as $def) {
      $wallet_access_plugins[$def['id']] = $def['label'];
    }
    $form['wallet_access'] = array(
    	'#title' => t('Default access of users to wallets'),
      '#description' => t('Determine which users can see, pay and pay from wallets by default, and which users can override their own wallets'),
      '#type' => 'details',
      '#weight' => 5
    );
    //TODO the following elements might need to be moved to somewhere where they can be re-used by the wallet's own config form.
    $form['wallet_access']['viewers'] = array(
      '#title' => t('Visible to'),
      '#description' => t('Who can see the balance and history of this wallet?'),
      '#type' => 'entity_chooser_selection',
      '#args' => array('user'),
      '#default_value' => $config->get('viewers'),
      '#weight' => 1,
    );
    $form['wallet_access']['payees'] = array(
      '#title' => t('Default payees'),
      '#description' => t('Who can create transactions out of this wallet?'),
      '#type' => 'entity_chooser_selection',
      '#args' => array('user'),
      '#default_value' => $config->get('payees'),
      '#weight' => 2,
    );
    $form['wallet_access']['payers'] = array(
      '#title' => t('Default payers'),
      '#description' => t('Who can create transactions into this wallet?'),
      '#type' => 'entity_chooser_selection',
      '#args' => array('user'),
      '#default_value' => $config->get('payers'),
      '#weight' => 3,
    );
    $form['wallet_access']['access_personalised'] = array(
      '#title' => t('Personalised wallet access'),
      '#description' => t('Users can adjust these settings, for every wallet'),
      '#type' => 'checkbox',
      '#default_value' => !$config->get('wallet_access_personalised'),
      '#weight' => 5
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

    $this->configFactory->get('mcapi.wallets')
      ->set('entity_types', $form_state['values']['entity_types'])
      ->set('viewers', $form_state['values']['viewers'])
      ->set('payers', $form_state['values']['payees'])
      ->set('payees', $form_state['values']['viewers'])
      ->set('unique_names', $form_state['values']['unique_names'])
      ->set('access_personalised', $form_state['values']['access_personalised'])
      ->set('autoadd_name', $form_state['values']['autoadd_name'])
      ->save();

    parent::submitForm($form, $form_state);
    //@todo why doesn't this show?
    drupal_set_message(t('Wallet settings are saved.'));

    $form_state['redirect_route'] = array(
      'route_name' => 'mcapi.admin'
    );
  }
}


