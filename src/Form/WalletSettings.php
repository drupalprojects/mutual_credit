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
  public function getFormID() {
    return 'mcapi_wallet_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, array &$form_state) {
    $config = $this->configFactory->get('mcapi.wallets');
    $form['creation'] = array(
    	'#title' => t('Wallet creation'),
      '#type' => 'fieldset',
      '#collapsed' => FALSE
    );
    $form['creation']['add_link_location'] = array(
      '#title' => t("Location of 'new wallet' link"),
      '#type' => 'radios',
      '#options' => array(
    	  'local_action' => t('As a local action on the entity display page'),
        'summaries' => t('In the wallet summaries block'),
        'both' => t('Both')
      ),
      '#default_value' => $config->get('add_link_location'),
      '#weight' => 1,
    );
    //A wallet can be attached to any entity with an entity reference field pointing towards the exchange entity
    //OR to an exchange entity itself
    $form['creation']['entity_types'] = array(
    	'#title' => t('Entity types'),
    	'#description' => t('Any content entity type which references exchanges can own wallets.') .' '.
        t('Put the maximum number of wallets per entity type.'),
      '#type' => 'fieldset',
      '#weight' => 2,
      '#tree' => TRUE
    );
    module_load_include('inc', 'mcapi');
    foreach (bundles_in_exchanges() as $entity_type => $bundles) {
      $entity_label = (count($bundles) > 1)
        ? \Drupal::entityManager()->getDefinition($entity_type, TRUE)->getLabel() .': '
        : '';
      foreach ($bundles as $bundle_name => $bundle_info) {
        $form['creation']['entity_types']["$entity_type:$bundle_name"] = array(
        	'#title' => $entity_label.$bundle_info['label'],
          '#type' => 'number',
          '#min' => 0,
          '#default_value' => $config->get("entity_types.$entity_type:$bundle_name"),
          '#size' => 2,
          '#max_length' => 2
        );
      }
    }
    $form['creation']['autoadd'] = array(
      '#title' => t('Auto-create a wallet for every new eligible entity'),
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

  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, array &$form_state) {
    $vals = &$form_state['values'];

    $this->configFactory->get('mcapi.wallets')
      ->set('entity_types', $vals['entity_types'])
      ->set('add_link_location', $vals['add_link_location'])
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


