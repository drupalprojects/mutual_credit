<?php

/**
 * @file
 * Definition of Drupal\mcapi\Form\WalletForm.
 * Edit all the fields on a transaction
 */

namespace Drupal\mcapi\Form;

use Drupal\Core\Entity\EntityFormController;
use Drupal\mcapi\TransactionViewBuilder;
use Drupal\mcapi\McapiTransactionException;
use Drupal\action\Plugin\Action;

class WalletForm extends EntityFormController {

  /**
   * Overrides Drupal\Core\Entity\EntityFormController::form().
   */
  public function form(array $form, array &$form_state) {

    $raw = \Drupal::request()->attributes->get('_raw_variables');


    drupal_set_title('wallet');

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
    );
    $form['entity_type'] = array(
    	'#type' => 'value',
      '#value' => $raw->get('entity_type')
    );
    $form['entity_id'] = array(
    	'#type' => 'value',
      '#value' => $raw->get('entity_id')
    );
    $pluginManager = \Drupal::service('plugin.manager.mcapi.wallet_access');

    foreach ($pluginManager->getDefinitions() as $def) {
      $plugins[$def['id']] = $def['label'];
    }
    if (\Drupal::currentUser()->hasPermission('set own wallet privacy') && 1) {
      $form['access'] = array(
        '#title' => t('Acccess settings'),
        '#type' => 'details',
        '#collapsible' => TRUE,
        'view' => array(
      	  '#title' => t('Who can view?'),
          '#type' => 'select',
          '#options' => $plugins
        )
      );
    }
    $form['proxies'] = array(
    	'#title' => t('Any other users who can trade from this wallet?'),
      '#type' => 'entity_chooser',
      '#plugin' => 'role',
      '#args' => array('authenticated'),//might want to form_alter this...
      '#default_value' => $wallet->proxies
    );
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  //@todo see what parent::save is doing, It is empty right now.
  function save(array $form, array &$form_state) {
    form_state_values_clean($form_state);
    debug($form_state['values']);
    foreach ($form_state['values'] as $key => $val) {
      $this->entity->{$key} = $val;
    }
    $this->entity->save();
  }

}

