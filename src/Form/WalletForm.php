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
use Drupal\mcapi\Mcapi;
use Drupal\mcapi\Entity\Wallet;
use Symfony\Component\DependencyInjection\ContainerInterface;

class WalletForm extends ContentEntityForm {

  private $walletConfig;

  /**
   *
   * @var /Drupal/Core/Entity/EntityTypeManager
   */
  ///private $entityTypeManager;

  public function __construct($config_factory, $entity_type_manager) {
    $this->walletConfig = $config_factory->get('mcapi.settings');
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('entity_type.manager')
    );
  }

  public function getFormId() {
    return 'wallet_form';
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {

    $form = parent::form($form, $form_state);

    if (!$this->entity->isAutonamed()) {
      $form['name'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Name or purpose of wallet'),
        '#default_value' => $this->entity->name->value,
        '#placeholder' => t('My excellent wallet'),
        '#required' => FALSE,
        '#maxlength' => 48,
        '#size' => 48
      ];
    }

    $walletableBundles = Mcapi::walletableBundles();
    foreach (array_keys($walletableBundles) as $entity_type_id) {
      $types[$entity_type_id] = $this->entityTypeManager->getDefinition($entity_type_id)->getLabel();
    }

    $form['transfer'] = [
      '#title' => t('Change holder'),
      '#type' => 'details',
      'holder_entity_type' => [
        '#title' => t('New holder type'),
        '#type' => 'select',
        '#options' => $types,
        '#default_value' => $this->entity->holder_entity_type->value,
        '#required' => FALSE,
        '#access' => $this->currentUser()->haspermission('manage exchange') && count($types) > 1
      ]
    ];

    foreach ($walletableBundles as $entity_type_id => $bundles) {
      $form['transfer'][$entity_type_id .'_entity_id'] = [
        '#title' => t('Name or unique ID'),
        '#type' => 'entity_autocomplete',
        '#placeholder' => t('@entityname name...', ['@entityname' => $types[$entity_type_id]]),
        '#states' => [
          'visible' => [
            ':input[name="holder_entity_type"]' => ['value' => $entity_type_id]
          ]
        ],
        '#autocomplete_route_name' => 'system.entity_autocomplete',
        '#target_type' => $entity_type_id,
        '#selection_handler' => 'default',//might want to change this but what are the options?
        '#selection_settings' => [
          'target_bundles' => $bundles
        ]
      ];
    }

    if (in_array($this->entity->payways->value, [Wallet::PAYWAY_ANYONE_OUT, Wallet::PAYWAY_ANYONE_BI])) {
      //that means anyone can pay out, so no need to nominate friends
      $form['payers']['#access'] = FALSE;
    }
    if (in_array($this->entity->payways->value, [Wallet::PAYWAY_ANYONE_IN, Wallet::PAYWAY_ANYONE_BI])) {
      //that means anyone can pay in, so no need to nominate friends
      $form['payees']['#access'] = FALSE;
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  function save(array $form, FormStateInterface $form_state) {
    $wallet = $this->entity;

    $values = $form_state->getValues();
    //update the wallet with any change of access


    //check for the change of holdership
    $key = $values['holder_entity_type'] . '_entity_id';
    if ($values[$key]) {
      //invalidate cache tags on the current parent.
      //@see Wallet::invalidateTagsOnSave to invalidate tags on the new parent.
      Cache::invalidateTags([$wallet->holder_entity_type->value . ':' . $wallet->holder_entity_id->value]);

      $wallet->set('holder_entity_type', $values['holder_entity_type'])
        ->set('holder_entity_id', $values[$key]);
      //@todo need to clear the walletaccess cache for both users
      drupal_set_message(
        t('Wallet has been transferred to %name', ['%name' => $wallet->getOwner()->label()])
      );
    }
    $wallet->save();
    $form_state->setRedirect(
      'entity.mcapi_wallet.canonical',
      ['mcapi_wallet' => $wallet->id()]
    );
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
      $names[] = $account->getDisplayName();
    }
    return implode(', ', $names);
  }

}
