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
use Drupal\mcapi\Exchange;
use Symfony\Component\DependencyInjection\ContainerInterface;

class WalletForm extends ContentEntityForm {

  private $walletConfig;

  public function __construct($config_factory) {
    $this->walletConfig = $config_factory->get('mcapi.wallets');

    $this->permissions = Exchange::walletPermissions();
    if ($wallet->entity_type !== 'user') {
      unset($this->permissions[WALLET_ACCESS_OWNER]);
    }
    $this->ops = Exchange::walletOps();
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory')
    );
  }

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
      $form['name'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Name or purpose of wallet'),
        '#default_value' => $wallet->name->value,
        '#placeholder' => t('My excellent wallet'),
        '#required' => FALSE,
        '#maxlength' => 48,
        '#size' => 48
      ];
    }

    $this->accessElement($form, 'details');
    $this->accessElement($form, 'summary');
    //anon users cannot pay in or out of wallets
    unset($this->permissions[WALLET_ACCESS_ANY]);
    $this->accessElement($form, 'payin');
    $this->accessElement($form, 'payout');

    if (array_key_exists('access', $form)) {
      $form['#attached']['library'][] = 'mcapi/mcapi.toolbar';
      $form['access'] += [
        '#title' => t('Acccess settings'),
        '#description' => t('Which users can do the following to this wallet?'),
        '#type' => 'details',
        '#open' => TRUE,
        '#collapsible' => TRUE,
      ];
    }

    $types = ['user' => t('User')];
    $form['transfer'] = [
      '#title' => t('Change ownership'),
      '#type' => 'details',
      'entity_type' => [
        '#title' => t('New owner type'),
        '#type' => 'select',
        '#options' => $types,
        '#default_value' => $this->entity->entity_type->value,
        '#required' => FALSE,
        '#access' => $this->currentUser()->haspermission('manage exchange')
      ]
    ];
    /*
    $form['transfer']['entity_id'] = array(
      '#title' => t('Unique ID'),
      '#description' => 'TODO make this work with autocompleted entity names',
      '#type' => 'number',
      '#weight' => 1,
      '#default_value' => $this->entity->pid->value,
      '#states' => array(
        'invisible' => array(
          ':input[name="entity_type"]' => array('value' => '')
        )
      ),
    );
     */

    //@todo I don't know how to later retrieve the entity from the label for an unknown entity type
    $autocomplete_routes = [
      'user' => 'user.autocomplete',
      //'mcapi_exchange' => 'mcapi.exchange.autocomplete'
    ];
    foreach ($types as $type => $label) {
      $id = $type .'_entity_id';
      $form['transfer'][$id] = [
        '#title' => t('Name or unique ID'),
        '#type' => 'textfield',
        '#placeholder' => t('@entityname name...', ['@entityname' => $label]),
        '#states' => [
          'visible' => [
            ':input[name="entity_type"]' => ['value' => $type]
          ]
        ],
        '#autocomplete_route_name' => 'system.entity_autocomplete',
        '#autocomplete_route_parameters' => [
          'target_type' => $type,
          'selection_handler' => 'default'//might want to change this but what are the options?
        ],
      ];
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  //It is empty right now but probably it will do all the below
  function save(array $form, FormStateInterface $form_state) {
    $wallet = $this->entity;

    $values = $form_state->getValues();
    foreach (Exchange::walletOps() as $op_name => $label) {
      $wallet->{$op_name}->value = $values[$op_name];
      $user_refs = [];
      if ($values[$op_name] == WALLET_ACCESS_OWNER) {
        $wallet->access[$op_name] = [$wallet->ownerUserId()];
      }
      elseif ($values[$op_name] = WALLET_ACCESS_USERS) {
        $wallet->access[$op_name] = [];
        foreach ($values[$op_name.'_users'] as $val) {
          $wallet->access[$op_name][] = $val['target_id'];
        }
      }
    }
    //check for the change of ownership
    if ($values['entity_type'] && $values['entity_id']) {
      $wallet->set('entity_type', $values['entity_type']);
      $wallet->set('pid', $values['entity_id']);
      entity_load($values['entity_type'], $values['entity_id']);
      drupal_set_message(
        t('Wallet has been transferred to !name', ['!name' => $wallet->label()])
      );
    }
    $wallet->save();
    $form_state->setRedirect(
      'entity.mcapi_wallet.canonical',
      ['mcapi_wallet' => $wallet->id()]
    );
  }

  private function accessElement(&$form, $op_name, $description) {
    $saved = $this->entity->access[$op_name];
    static $weight = 0;
    $system_default = array_filter($this->walletConfig->get($op_name));
    if (count($system_default) > 1) {
      $form['access'][$op_name] = [
        '#title' => $this->ops[$op_name][0],
        '#description' => $this->ops[$op_name][1],
        '#type' => 'select',
        '#options' => array_intersect_key($this->permissions, $system_default),
        '#default_value' => is_array($saved) ? WALLET_ACCESS_USERS : $saved,
        '#weight' => $weight++,
      ];
      $form['access'][$op_name . '_users'] = [
        '#title' => '...'. $this->t('Specified users'),
        '#type' => 'entity_autocomplete',
        '#autocomplete_route_name' => 'system.entity_autocomplete',
        '#target_type' => 'user',
        '#tags' => TRUE,
        '#placeholder' => $this->t('@entityname name...', ['@entityname' => t('User')]),
        '#default_value' => is_array($saved) ? User::loadMultiple($saved) : [],
        '#states' => [
          'visible' => [
            ':input[name="' . $op_name . '"]' => ['value' => WALLET_ACCESS_USERS]
          ]
        ],
        '#weight' => $weight++,
      ];
    }
    else {
      $form[$op_name] = [
        '#type' => 'value',
        '#value' => current($system_default)
      ];
    }
  }

  /**
   *
   * @param array $uids
   * @return string
   *   comma separated usernames
   */
  private function getUsernames($uids) {
    $names = [];
    foreach (User::loadMultiple($uids) as $account) {
      $names[] = $account->getUsername();
    }
    return implode(', ', $names);
  }

}
