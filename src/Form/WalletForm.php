<?php

/**
 * @file
 * Definition of Drupal\mcapi\Form\WalletForm.
 * Edit all the fields on a wallet
 */

namespace Drupal\mcapi\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\user\Entity\User;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Component\Utility\Tags;

class WalletForm extends ContentEntityForm {

  public function getFormId() {
    return 'wallet_form';
  }


  /**
   * Overrides Drupal\Core\Entity\ContentEntityForm::form().
   */
  public function form(array $form, FormStateInterface $form_state) {

    $form = parent::form($form, $form_state);
    $wallet = $this->entity;

    unset($form['langcode']); // No language so we remove it.

    if ($wallet->name->value != '_intertrading') {
      $form['name'] = array(
        '#type' => 'textfield',
        '#title' => t('Name or purpose of wallet'),
        '#default_value' => $wallet->name->value,
        '#placeholder' => t('My excellent wallet'),
        '#required' => FALSE,
        '#max_length' => 32//TODO check this is the right syntax
      );
    }

    $this->permissions = $this->entity->permissions();
    $this->permissions['owner'] = t('The owner');

    $this->default_wallet_access = \Drupal::config('mcapi.wallets');

    $this->accessElement($form, 'details', t('View transaction details'), t('View individual transactions this wallet was involved in'), $this->entity->access['details']);
    $this->accessElement($form, 'summary', t('View summary'), t('The balance, number of transactions etc.'), $this->entity->access['summary']);
    //anon users cannot pay in or out of wallets
    unset($this->permissions[WALLET_ACCESS_ANY]);
    $this->accessElement($form, 'payin', t('Pay in'), t('Create payments into this wallet'), $this->entity->access['payin']);
    $this->accessElement($form, 'payout', t('Pay out'), t('Create payments out of this wallet'), $this->entity->access['payout']);

    if (array_key_exists('access', $form)) {
      $form['access'] += array(
        '#title' => t('Acccess settings'),
        '#description' => t('Which users can do the following to this wallet?'),
        '#type' => 'details',
        '#open' => TRUE,
        '#collapsible' => TRUE,
      );
    }

    $types = array('user' => t('User'));
    $form['transfer'] = array(
    	'#title' => t('Change ownership'),
      '#type' => 'details',
      'entity_type' => array(
    	  '#title' => t('New owner type'),
        '#type' => 'select',
        '#options' => array('' => 'Choose') + $types,
        '#required' => FALSE,
        '#access' => \Drupal::currentUser()->haspermission('manage exchange')
      )
    );

    /* I don't know how to later retrieve the entity from the label for an unknown entity type
    $autocomplete_routes = array(
    	'user' => 'user.autocomplete',
      'mcapi_exchange' => 'mcapi.exchange.autocomplete'
    );
    foreach ($types as $type => $label) {
      $id = $type .'_entity_id';
      $form['transfer'][$id] = array(
      	'#title' => t('Name or unique ID'),
        '#type' => 'textfield',
        '#placeholder' => '0',
        '#states' => array(
          'visible' => array(
            ':input[name="entity_type"]' => array('value' => $type)
          )
        ),
      );
      if (array_key_exists($type, $autocomplete_routes)) {
        $form['transfer'][$id]['#autocomplete_route_name'] = $autocomplete_routes[$type];
        //$form['transfer'][$id]['#autocomplete_route_parameters'] = array();
      }
    }
    */
    $form['transfer']['entity_id'] = array(
      '#title' => t('Unique ID'),
      '#description' => 'TODO make this work with autocompleted entity names',
      '#type' => 'number',
      '#weight' => 1,
      '#states' => array(
        'invisible' => array(
          ':input[name="entity_type"]' => array('value' => '')
        )
      ),
    );


    return $form;
  }

  /**
   * {@inheritdoc}
   */
  //@todo see what parent::save is doing after alpha12
  //It is empty right now but probably it will do all the below
  function save(array $form, FormStateInterface $form_state) {
    form_state_values_clean($form_state);
    $values = $form_state->getValues();
    foreach ($this->entity->ops() as $op_name) {
      if ($values[$op_name] == WALLET_ACCESS_OWNER) {
        $values[$op_name] = WALLET_ACCESS_USERS;
        $owner_user = User::load($this->entity->user_id());
        $values[$op_name.'_users'] = $owner_user->getUsername();
      }
      if ($values[$op_name] == WALLET_ACCESS_USERS) {
        //TODO isn't there a function for getting the user objects out of the user autocomplete multiple?
        //or at least for exploding tags
        $usernames = Tags::explode($values[$op_name.'_users']);
        $users = entity_load_multiple_by_properties('user', array('name' => $usernames));
        $this->entity->access[$op_name] = array_keys($users);
      }
      else $this->entity->access[$op_name] = $values[$op_name];
    }

    //check for the change of ownership
    if (isset($values['entity_type']) && isset($values['entity_id'])) {
      $this->entity->set('entity_type', $values['entity_type']);
      $this->entity->set('pid', $values['entity_id']);
      entity_load($values['entity_type'], $values['entity_id']);
      drupal_set_message(t('Wallet has been transferred to !name', array('!name' => $entity->getLabel())));
    }
    $this->entity->save();
    $form_state->setRedirect('mcapi.wallet_view', array('mcapi_wallet' => $this->entity->id()));
  }


  private function accessElement(&$form, $op_name, $title, $description, $saved) {
    static $weight = 0;
    $system_default = array_filter($this->default_wallet_access->get($op_name));
    if (count($system_default) > 1) {
      $form['access'][$op_name] = array(
        '#title' => $title,
        '#description' => $description,
        '#type' => 'select',
        '#options' => array_intersect_key($this->permissions, $system_default),
        '#default_value' => is_array($saved) ? WALLET_ACCESS_USERS : $saved,
        '#weight' => $weight++
      );
      $form['access'][$op_name.'_users'] = array(
        '#title' => t('Named users'),
        '#type' => 'textfield',
        '#autocomplete_route_name' => 'user.autocomplete',
        '#multiple' => TRUE,
        '#default_value' => is_array($saved) ? $this->getUsernames($saved) : '',
        '#states' => array(
          'visible' => array(
            ':input[name="'.$op_name.'"]' => array('value' => WALLET_ACCESS_USERS)
          )
        ),
        '#weight' => $weight++
      );
    }
    else {
      $form[$op_name] = array(
        '#type' => 'value',
        '#value' => current($system_default)
      );
    }
  }

  /**
   *
   * @param array $uids
   * @return string
   *   comma separated usernames
   */
  private function getUsernames($uids) {
    $names = array();
    foreach (User::loadMultiple($uids) as $account) {
      $names[] = $account->getUsername();
    }
    return implode(', ', $names);
  }
}

