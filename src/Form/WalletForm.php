<?php

/**
 * @file
 * Definition of Drupal\mcapi\Form\WalletForm.
 * Edit all the fields on a wallet
 */

namespace Drupal\mcapi\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\user\Entity\User;

class WalletForm extends ContentEntityForm {

  public function getFormId() {
    return 'wallet_form';
  }


  /**
   * Overrides Drupal\Core\Entity\ContentEntityForm::form().
   */
  public function form(array $form, array &$form_state) {

    $form = parent::form($form, $form_state);
    $wallet = $this->entity;

    unset($form['langcode']); // No language so we remove it.

    $form['name'] = array(
      '#type' => 'textfield',
      '#title' => t('Name or purpose of wallet'),
      '#default_value' => $wallet->name->value,
      '#placeholder' => t('My excellent wallet'),
      '#required' => FALSE,
      '#max_length' => 32//TODO check this is the right syntax
    );

    $this->permissions = $this->entity->permissions();
    $this->permissions['owner'] = t('The owner');

    $this->default_wallet_access = \Drupal::config('mcapi.wallets');
print_r($this->entity->access);
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

    $form['transfer'] = array(
    	'#title' => t('Change ownership'),
      '#type' => 'details',
      'newowner' => array(
    	  '#title' => t('New owner'),
        '#type' => 'textfield',
        '#placeholder' => 'not implemented yet'
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
    foreach ($this->entity->ops() as $op_name) {
      if ($form_state['values'][$op_name] == WALLET_ACCESS_OWNER) {
        $form_state['values'][$op_name] = WALLET_ACCESS_USERS;
        $form_state['values'][$op_name.'_users'] = $this->entity->getOwner()->getUsername();
drupal_set_message('replaced owner with user '.$this->entity->getOwner()->getUsername());
      }
      if ($form_state['values'][$op_name] == WALLET_ACCESS_USERS) {
        //TODO isn't there a function for getting the user objects out of the user autocomplete multiple?
        //or at least for exploding tags
        $usernames = explode(',', $form_state['values'][$op_name.'_users']);
        $users = entity_load_multiple_by_properties('user', array('name' => $usernames));
        $this->entity->access[$op_name] = array_keys($users);
      }
      else $this->entity->access['$op_name'] = $form_state['values'][$op_name];
    }
    $this->entity->save();
    $form_state['redirect_route'] = array(
      'route_name' => 'mcapi.wallet_view',
      'route_parameters' => array('mcapi_wallet' => $this->entity->id())
    );
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

