<?php

/**
 * @file
 * Definition of Drupal\mcapi\Form\WalletForm.
 * Edit all the fields on a wallet
 */

namespace Drupal\mcapi\Form;

use Drupal\Core\Entity\ContentEntityForm;

class WalletForm extends ContentEntityForm {

  public function getFormId() {
    return 'wallet_form';
  }


  /**
   * Overrides Drupal\Core\Entity\ContentEntityForm::form().
   */
  public function form(array $form, array &$form_state) {

    $raw = \Drupal::request()->attributes->get('_raw_variables');

    $form = parent::form($form, $form_state);
    $wallet = $this->entity;

    unset($form['langcode']); // No language so we remove it.

    $form['wid'] = array(
      '#type' => 'value',
      '#value' => property_exists($wallet, 'wid') ? $wallet: NULL,
    );
    $form['name'] = array(
      '#type' => 'textfield',
      '#title' => t('Name or purpose of wallet'),
      '#default_value' => $wallet->name->value,
      '#placeholder' => t('My excellent wallet'),
      '#required' => FALSE,
      '#max_length' => 32//TODO check this is the right syntax
    );
    $form['entity_type'] = array(
    	'#type' => 'value',
      '#value' => $raw->get('entity_type'),
    );
    $form['pid'] = array(
    	'#type' => 'value',
      '#value' => $raw->get('pid')
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
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  //@todo see what parent::save is doing after alpha12
  //It is empty right now but probably it will do all the below
  function save(array $form, array &$form_state) {
    form_state_values_clean($form_state);
    foreach ($form_state['values'] as $key => $val) {
      $this->entity->{$key} = $val;
    }
    $this->entity->save();
  }

}
