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

    foreach (entity_get_info() as $name => $definition) {
      if (array_key_exists('config_prefix', $definition)) continue;
      if (in_array($name, array('menu_link', 'mcapi_wallet', 'mcapi_transaction'))) continue;
      $types[$name] = $definition['label'];
    }
    //@todo GORDON how about simply limiting the entity types to node, exchange and user?
    $form['types'] = array(
    	'#title' => t('Entity types'),
      '#description' => t('Which content entity types can own wallets?'),
      '#type' => 'select',
      '#options' => $types,
      '#default_value' => $config->get('types'),
      '#multiple' => TRUE,
      '#weight' => -5
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
    $form['wallet_unique_names'] = array(
      '#title' => t('Unique wallet names'),
      '#type' => 'checkbox',
      '#default_value' => !$config->get('wallet_unique_names'),
      '#weight' => 7
    );
    $form['wallet_access_personalised'] = array(
      '#title' => t('Personalised wallet access'),
      '#description' => t('Users can adjust these settings, for every wallet'),
      '#type' => 'checkbox',
      '#default_value' => !$config->get('wallet_access_personalised'),
      '#weight' => 7
    );
    $form['wallet_autoadd'] = array(
      '#title' => t('Auto-add wallets'),
      '#description' => t('Create a new wallet automatically for each new user'),
      '#type' => 'checkbox',
      '#default_value' => !$config->get('wallet_autoadd'),
      '#weight' => 9
    );
    $form['wallet_one'] = array(
      '#title' => t('One wallet'),
      '#description' => t('One wallet only per parent entity - makes choosing easier.'),
      '#type' => 'checkbox',
      '#default_value' => !$config->get('wallet_one'),
      '#weight' => 9
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
      ->set('types', $form_state['values']['types'])
      ->set('wallet_access', $form_state['values']['wallet_access'])
      ->set('wallet_unique_names', $form_state['values']['wallet_unique_names'])
      ->set('wallet_access_personalised', $form_state['values']['wallet_access_personalised'])
      ->set('wallet_autoadd', $form_state['values']['wallet_autoadd'])
      ->set('wallet_one', $form_state['values']['wallet_one'])
      ->save();

    parent::submitForm($form, $form_state);
  }
}


