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
    $form['entity_types'] = array(
    	'#title' => t('Entity types'),
    	'#description' => t('Put the maximum number of wallets that entities of each type can hold.') .' '.
        t('Only bundles with entity reference fields to Exchanges are shown.'),
      '#type' => 'details',
      '#tree' => TRUE
    );
    module_load_include('inc', 'mcapi');
    foreach (get_exchange_entity_fieldnames() as $entity_type => $fieldname) {
      $entity_info = \Drupal::entityManager()->getDefinition($entity_type, TRUE);
      foreach (entity_get_bundles($entity_type) as $bundle => $bundle_info) {
        $title = $entity_definition['label'] == $bundle_info['label'] ?
          $entity_definition['label'] : //don't know if this is translated or translatable!
          $entity_definition['label'] .':'. $bundle_info['label'];

        $form['entity_types']["$entity_type:$bundle"] = array(
        	'#title' => $title,
          '#type' => 'number',
          '#min' => 0,
          '#default_value' => $config->get("entity_types.$entity_type:$bundle"),
          '#size' => 2,
          '#max_length' => 2
        );
      }
    }
    $form['autoadd'] = array(
      '#title' => t('Auto-create a wallet for every new user'),
      '#description' => t('This is not retrospective'),
      '#type' => 'checkbox',
      '#default_value' => $config->get('autoadd'),
      '#weight' => 3,
    );

    foreach ($this->pluginManager = \Drupal::service('plugin.manager.mcapi.wallet_access')->getDefinitions() as $def) {
      $wallet_access_plugins[$def['id']] = $def['label'];
    }
    $form['wallet_access'] = array(
    	'#title' => t('Default access of users to wallets'),
      '#description' => t('Determine which users can see, pay and pay from wallets by default, and which users can override their own wallets.') .
        ' ACCESS CONTROL HAS NOT BEEN IMPLEMENTED YET - ALL WALLETS ARE TOTALLY VISIBLE',
      '#type' => 'details',
      '#weight' => 5
    );
    //TODO the following elements might need to be moved to somewhere where they can be re-used by the wallet's own config form.
/*    $form['wallet_access']['viewers'] = array(
      '#title' => t('Visible to'),
      '#description' => t('Who can see the balance and history of this wallet?'),
      '#default_value' => $config->get('viewers'),
      '#weight' => 1,
    );
    $form['wallet_access']['payees'] = array(
      '#title' => t('Default payees'),
      '#description' => t('Who can create transactions out of this wallet?'),
      '#default_value' => $config->get('payees'),
      '#weight' => 2,
    );
    $form['wallet_access']['payers'] = array(
      '#title' => t('Default payers'),
      '#description' => t('Who can create transactions into this wallet?'),
      '#default_value' => $config->get('payers'),
      '#weight' => 3,
    );
    $form['wallet_access']['access_personalised'] = array(
      '#title' => t('Personalised wallet access'),
      '#description' => t('Users can adjust these settings, for every wallet'),
      '#type' => 'checkbox',
      '#default_value' => !$config->get('wallet_access_personalised'),
      '#weight' => 5
    );*/

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
    $vals = &$form_state['values'];

    $this->configFactory->get('mcapi.wallets')
      ->set('entity_types', $vals['entity_types'])
      //->set('viewers', $vals['viewers'])
      //->set('payers', $vals['payees'])
      //->set('payees', $vals['viewers'])
      //->set('access_personalised', $vals['access_personalised'])
      ->set('autoadd', $vals['autoadd'])
      ->save();

    parent::submitForm($form, $form_state);

    $form_state['redirect_route'] = array(
      'route_name' => 'mcapi.admin'
    );
  }
}


