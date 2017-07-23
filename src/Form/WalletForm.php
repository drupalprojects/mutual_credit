<?php

namespace Drupal\mcapi\Form;

use Drupal\mcapi\Mcapi;
use Drupal\user\Entity\User;
use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Build form to edit, not create, the wallet entity.
 */
class WalletForm extends ContentEntityForm {


  /**
   * The wallet settings.
   */
  private $walletConfig;

  /**
   * Constructor.
   */
  public function __construct($config_factory, $time) {
    //parent::__construct($entity_manager, $entity_type_bundle_info, $time); // seems not to be needed
    $this->walletConfig = $config_factory->get('mcapi.settings');
    $this->time = $time ?: \Drupal::service('datetime.time');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('datetime.time')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'wallet_form';
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {

    $form['#title'] = $this->t("Edit %name wallet", ['%name' => $this->entity->label()]);

    $form = parent::form($form, $form_state);

    $form['name'] = [
      '#title' => $this->t('Name or purpose of wallet'),
      '#description' => $this->t('The wallet name is used in the transaction form.'),
      '#type' => 'textfield',
      '#default_value' => $this->entity->name->value,
      '#placeholder' => $this->t("@name - personal", ['@name' => $this->currentUser()->getDisplayName()]),
      '#required' => FALSE,
      '#maxlength' => 48,
      '#size' => 48,
      //populate the name only if there is more than one wallet
      '#access' => Mcapi::maxWalletsOfBundle($this->entity->getHolder()->getEntityTypeId(), $this->entity->getHolder()->bundle()) > 1
    ];


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
        '#access' => $this->currentUser()->haspermission('manage exchange') && count($types) > 1,
      ],
    ];

    foreach ($walletableBundles as $entity_type_id => $bundles) {
      $form['transfer'][$entity_type_id . '_entity_id'] = [
        '#title' => t('Name or unique ID'),
        '#type' => 'entity_autocomplete',
        '#placeholder' => t('@entityname name...', ['@entityname' => $types[$entity_type_id]]),
        '#states' => [
          'visible' => [
            ':input[name="holder_entity_type"]' => ['value' => $entity_type_id],
          ],
        ],
        '#autocomplete_route_name' => 'system.entity_autocomplete',
        '#target_type' => $entity_type_id,
        // Might want to change this but what are the options?
        '#selection_handler' => 'default',
        '#selection_settings' => [
          'target_bundles' => $bundles,
        ],
      ];
    }
    $form['#attached']['library'][] = 'mcapi/mcapi.wallets';
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $wallet = $this->entity;

    $values = $form_state->getValues();
    // Update the wallet with any change of access
    // check for the change of holdership.
    $key = $values['holder_entity_type'] . '_entity_id';
    if ($values[$key]) {
      // Invalidate cache tags on the current parent.
      // @see Wallet::invalidateTagsOnSave to invalidate tags on the new parent.
      Cache::invalidateTags([$wallet->holder_entity_type->value . ':' . $wallet->holder_entity_id->value]);

      $wallet->set('holder_entity_type', $values['holder_entity_type'])
        ->set('holder_entity_id', $values[$key]);
      // @todo need to clear the walletaccess cache for both users
      drupal_set_message(
        t('Wallet has been transferred to %name', ['%name' => $wallet->getOwner()->label()])
      );
    }
    $form_state->setRedirect(
      'entity.mcapi_wallet.canonical',
      ['mcapi_wallet' => $wallet->id()]
    );
    parent::save($form, $form_state);
  }

  /**
   * Get a list of user names from the user IDs.
   *
   * @param array $uids
   *   User IDs.
   *
   * @return string
   *   Comma separated usernames.
   */
  private function getUsernames($uids) {
    $names = [];
    foreach (User::loadMultiple($uids) as $account) {
      $names[] = $account->getDisplayName();
    }
    return implode(', ', $names);
  }

}
