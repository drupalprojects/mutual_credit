<?php

namespace Drupal\mcapi\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Url;
use Drupal\mcapi\Exchange;
use Drupal\mcapi\Entity\Wallet;
use Symfony\Component\DependencyInjection\ContainerInterface;

class WalletSettings extends ConfigFormBase {

  private $entityTypeManager;

  public function __construct($configFactory, $entityTypeManager, $entity_type_bundle_info) {
    $this->setConfigFactory($configFactory);
    $this->entityTypeManager = $entityTypeManager;
    $this->entityTypeBundleInfo = $entity_type_bundle_info;
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
  static public function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('entity_type.manager'),
      $container->get('entity_type.bundle.info')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->configFactory()->get('mcapi.settings');

    //A wallet can be attached to any entity with an entity reference field pointing towards the exchange entity
    //OR to an exchange entity itself
    $link = \Drupal::l(
      'EntityOwnerInterface',
      Url::fromUri('https://api.drupal.org/api/drupal/core!modules!user!src!EntityOwnerInterface.php/interface/EntityOwnerInterface/8')
    );
    $form['entity_types'] = [
      '#title' => $this->t('Max number of wallets'),
      '#description' => $this->t(
        "Wallets can be owned by any entity type which implements !interface and has an entity_references field to 'exchange' entities.",
        ['!interface' => $link]
      ),
      '#type' => 'fieldset',
      '#weight' => 2,
      '#tree' => TRUE
    ];

    foreach ($this->entityTypeManager->getDefinitions() as $entity_type_id => $entity_type) {
      //tricky to know which entities to show here.
      if ($entity_type->isSubclassOf('\Drupal\Core\Entity\FieldableEntityInterface')
        && ($entity_type->isSubclassOf('\Drupal\User\EntityOwnerInterface') || $entity_type_id == 'user')
        && $entity_type->getLinkTemplate('canonical')//otherwise where to put the wallet!
        ) {
        $bundles = $this->entityTypeBundleInfo->getBundleInfo($entity_type_id);
        $entity_label = (count($bundles) > 1)
          ? $entity_type->getLabel() .': '
          : '';
        foreach ($bundles as $bundle_name => $bundle_info) {
          $form['entity_types']["$entity_type_id:$bundle_name"] = [
            '#title' => $entity_label.$bundle_info['label'],
            '#type' => 'number',
            '#min' => 0,
            '#default_value' => $config->get("entity_types.$entity_type_id:$bundle_name"),
            '#size' => 2,
            '#max_length' => 2
          ];
        }
      }
    }
    $form['wallet_tab'] = [
      '#title' => $this->t('Show wallets tab on canonical entity page'),
      '#description' => $this->t("Tab would show 'summary' view of each wallet owned by the entity. Otherwise show links to wallets canonical pages using the entity's display fields"),
      '#type' => 'checkbox',
      '#default_value' => $config->get('wallet_tab'),
      '#weight' => 3,
    ];
    $form['wallet_inex_tab'] = [
      '#title' => $this->t('Show income & expenditure tab on wallet page'),
      '#description' => $this->t("Alternatively include it in a wallet view mode"),
      '#type' => 'checkbox',
      '#default_value' => $config->get('wallet_inex_tab'),
      '#weight' => 3,
    ];
    $form['wallet_log_tab'] = [
      '#title' => $this->t('Show transaction log tab on wallet page'),
      '#description' => $this->t("Alternatively include it in a wallet view mode"),
      '#type' => 'checkbox',
      '#default_value' => $config->get('wallet_log_tab'),
      '#weight' => 3,
    ];
    $form['autoadd'] = [
      '#title' => $this->t('Auto-create'),
      '#description' => $this->t('A wallet will be auto-created for when entities of each wallet-holding type above is created.') .' '.t('This is not retroactive'),
      '#type' => 'checkbox',
      '#default_value' => $config->get('autoadd'),
      '#weight' => 5,
    ];
    $form['render_unused'] = [
      '#title' => $this->t('Render unused'),
      '#description' => $this->t('Show stats for unused currencies in wallet.'),
      '#type' => 'checkbox',
      '#default_value' => $config->get('render_unused'),
      '#weight' => 7,
    ];
    

    $permissions = ['o' =>  $this->t('The wallet owner')] + Exchange::walletPermissions();

    $form['wallet_access'] = [
      '#title' => t('Default access of users to wallets'),
      '#description' => t('Determine which users can see, pay and charge from wallets by default.') .' '.
        t("If more than one box is checked, the first one will be the default for new wallets, and the owner of the wallet will be allowed to configure on their wallet 'edit tab'."),
      '#type' => 'details',
      '#weight' => 9
    ];
    /* FYI
    Exchange::walletOps() == [
      [details] => View transaction log
      [summary] => View summary
      [payin] => Pay into this wallet
      [payout] => Pay out of this wallet
    ]*/
    $w = 0;
    foreach (Exchange::walletOps() as $key => $blurb) {
      $form['wallet_access'][$key] = [
        '#title' => $blurb[0],
        '#description' => $blurb[1],
        '#type' => 'checkboxes',
        '#options' => $permissions,
        '#default_value' => $config->get($key),
        '#weight' => $w++,
        '#required' => TRUE
      ];
    }
    $form['wallet_access']['details']['o']['#default_value'] = TRUE;
    $form['wallet_access']['details']['o']['#disabled'] = TRUE;
    $form['wallet_access']['summary']['o']['#default_value'] = TRUE;
    $form['wallet_access']['summary']['o']['#disabled'] = TRUE;
    unset($form['wallet_access']['payin']['#options'][Wallet::ACCESS_ANY]);
    unset($form['wallet_access']['payout']['#options'][Wallet::ACCESS_ANY]);

    $form['user_interface'] = [
      '#title' => $this->t('User interface'),
      '#type' => 'details',
      'threshhold' => [
        '#title' => $this->t('Threshhold'),
        '#description' => $this->t('If there are more wallets to choose from than this number, the autocomplete widget will be used.'),
        '#type' => 'number',
        '#default_value' => $config->get('threshhold'),
        '#required' => TRUE
      ],
      'widget' => [
        '#title' => $this->t('Widget'),
        '#description' => $this->t('The preferred widget to select from a small number of wallets.'),
        '#type' => 'radios',
        '#options' => [
          'select' => $this->t('A dropdown box'),
          'radios' => $this->t('Radio buttons')
        ],
        '#default_value' => $config->get('widget'),
        '#required' => TRUE
      ],
      '#weight' => 12
    ];

    return parent::buildForm($form, $form_state);
  }


  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    foreach (array_keys(Exchange::walletOps()) as $op_name => $blurb) {
      if (array_filter($values[$op_name]) == [Wallet::ACCESS_USERS => Wallet::ACCESS_USERS]) {
        $form_state->setErrorByName($op_name, t("'Named users' cannot be selected by itself"));
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $vals = $form_state->getValues('values');
    $this->configFactory->getEditable('mcapi.settings')
      ->set('entity_types', $vals['entity_types'])
      ->set('wallet_tab', $vals['wallet_tab'])
      ->set('wallet_inex_tab', $vals['wallet_inex_tab'])
      ->set('wallet_log_tab', $vals['wallet_log_tab'])
      ->set('autoadd', $vals['autoadd'])
      ->set('details', array_filter($vals['details']))
      ->set('summary', array_filter($vals['summary']))
      ->set('payin', array_filter($vals['payin']))
      ->set('payout', array_filter($vals['payout']))
      ->set('threshhold', $vals['threshhold'])
      ->set('widget', $vals['widget'])
      ->save();

    parent::submitForm($form, $form_state);

    Cache::invalidateTags(['mcapi_wallet_values', 'mcapi_wallet_view']);

    $form_state->setRedirect('mcapi.admin');
  }

  protected function getEditableConfigNames() {
    return ['mcapi.settings'];
  }

}



