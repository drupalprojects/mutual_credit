<?php

/**
 * @file
 * Definition of Drupal\mcapi\Form\WalletAddForm.
 * Add a new wallet from url parameters
 */

namespace Drupal\mcapi\Form;

use \Drupal\Core\Form\FormBase;

class WalletAddForm extends Formbase {

  public function getFormId() {
    return 'wallet_add_form';
  }


  public function buildForm(array $form, array &$form_state) {
    //its a bloody hassle to get the params out when it is all protected variables and no methods to access them
    $owner = mcapi_request_get_entity(\Drupal::request());

    drupal_set_title(t("New wallet for '!title'", array('!title' => $owner->label())));

    $form['wid'] = array(
      '#type' => 'value',
      '#value' => NULL,
    );
    $form['name'] = array(
      '#type' => 'textfield',
      '#title' => t('Name or purpose of wallet'),
      '#default_value' => '',
    );
    $form['entity_type'] = array(
    	'#type' => 'value',
      '#value' => $owner->getEntityTypeId()
    );
    $form['pid'] = array(
    	'#type' => 'value',
      '#value' => $owner->id()
    );
    $pluginManager = \Drupal::service('plugin.manager.mcapi.wallet_access');

    foreach ($pluginManager->getDefinitions() as $def) {
      $plugins[$def['id']] = $def['label'];
    }

    $form['access'] = array(
      '#title' => t('Acccess settings'),
      '#type' => 'details',
      '#collapsible' => TRUE,
      'viewers' => array(
    	  '#title' => t('Who can view?'),
        '#type' => 'select',
        '#options' => $plugins
      ),
      'payees' => array(
    	  '#title' => t('Who can request from this wallet?'),
        '#type' => 'select',
        '#options' => $plugins
      ),
      'payers' => array(
    	  '#title' => t('Who can contribute to this wallet?'),
        '#type' => 'select',
        '#options' => $plugins
      )
    );
    $form['submit'] = array(
    	'#type' => 'submit',
      '#value' => t('Create')
    );
    return $form;
  }

  function validateForm(array &$form, array &$form_state) {
    //just check that the name isn't the same
    //if there was a wallet storage controller this unique checking would happen there.
    $query = db_select('mcapi_wallets', 'w')
    ->fields('w', array('wid'))
    ->condition('name', $form_state['values']['name']);

    if (!\Drupal::config('mcapi.wallets')->get('unique_names')) {
      $query->condition('pid', $form_state['values']['pid']);
      $query->condition('entity_type', $form_state['values']['entity_type']);
    }
    if ($query->execute()->fetchField()) {
      $this->setFormError('name', $form_state, t("The wallet name '!name' is already used.", array('!name' => $form_state['values']['name'])));
    }
  }

  /**
   * {@inheritdoc}
   */
  function submitForm(array &$form, array &$form_state) {
    form_state_values_clean($form_state);
    $wallet = entity_create('mcapi_wallet', $form_state['values']);
    $wallet->save();
    $pid = $wallet->get('pid')->value;
    $info = entity_load($wallet->get('entity_type')->value, $pid)->entityInfo();
    $form_state['redirect_route'] = array(
      'route_name' => $info['links']['canonical'],
      'route_parameters' => array($info['id'] => $pid)
    );
  }

}

