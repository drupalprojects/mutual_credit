<?php

namespace Drupal\mcapi\Form;

use Drupal\Core\Entity\Entity\EntityFormDisplay;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Url;
use Drupal\Core\Link;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form builder for wallet settings.
 */
class WalletSettings extends ConfigFormBase {

  /**
   * The entityTypeManager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The entity type bundle info service.
   *
   * @var type
   */
  protected $entityTypeBundleInfo;

  /**
   * Construct.
   */
  public function __construct($configFactory, $entityTypeManager, $entity_type_bundle_info) {
    $this->setConfigFactory($configFactory);
    $this->entityTypeManager = $entityTypeManager;
    $this->entityTypeBundleInfo = $entity_type_bundle_info;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
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
    // Display a warning message.
    $display = EntityFormDisplay::load('mcapi_wallet.mcapi_wallet.default');
    $config = $this->configFactory()->get('mcapi.settings');

    // A wallet can be attached to any entity with an entity reference field
    // pointing towards the exchange entity OR to an exchange entity itself.
    $link = Link::fromTextAndUrl(
      'EntityOwnerInterface',
      Url::fromUri('https://api.drupal.org/api/drupal/core!modules!user!src!EntityOwnerInterface.php/interface/EntityOwnerInterface/8')
    );
    $form['entity_types'] = [
      '#title' => $this->t('Max number of wallets'),
      '#description' => $this->t("Where only one wallet is allowed, that wallet will inherit the name of the entity which holds it"),
      '#type' => 'fieldset',
      '#weight' => 2,
      '#tree' => TRUE,
    ];

    // @todo alter this in the exchanges module so that only bundles with an
    // entity reference to an exchange are listed
    foreach ($this->entityTypeManager->getDefinitions() as $entity_type_id => $entity_type) {
      if ($entity_type_id == 'mcapi_transaction') {
        continue;
      }
      if ($entity_type->isSubclassOf('\Drupal\Core\Entity\ContentEntityInterface')) {
        if (($entity_type->isSubclassOf('\Drupal\User\EntityOwnerInterface') || $entity_type_id == 'user')
        // Otherwise where to put the wallet!
          && $entity_type->getLinkTemplate('canonical')
          ) {
          $bundles = $this->entityTypeBundleInfo->getBundleInfo($entity_type_id);
          $entity_label = (count($bundles) > 1)
            ? $entity_type->getLabel() . ': '
            : '';
          foreach ($bundles as $bundle_name => $bundle_info) {
            $val = $config->get("entity_types.$entity_type_id:$bundle_name");
            /* @var $bundle_name string */
            $form['entity_types']["$entity_type_id:$bundle_name"] = [
              '#title' => $entity_label . $bundle_info['label'],
              '#type' => 'number',
              '#min' => $val,
              '#default_value' => $val,
              '#size' => 2,
              '#max_length' => 2,
            ];
          }
        }
      }
    }
    $form['autoadd'] = [
      '#title' => $this->t('Auto-create'),
      '#description' => implode(' ', [
        $this->t('A wallet will be auto-created for when entities of each wallet-holding type above is created.'),
        $this->t('This is not retroactive.'),
      ]),
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
    $form['public'] = [
      '#title' => $this->t('Default wallet visibility'),
      '#description' => $this->t('Setting given to new wallets.') . ' NOT IMPLEMENTED',
      '#type' => 'radios',
      '#options' => [
        0 => $this->t('Can only be seen by the owners and admins'),
        1 => $this->t("Can be seen by anybody with 'view public wallets' permission"),
      ],
      //'#default_value' => $config->get('public'),
      '#disabled' => TRUE,
      '#weight' => 9,
    ];

    $form['user_interface'] = [
      '#title' => $this->t('User interface'),
      '#type' => 'details',
      '#weight' => 12,
    ];

    $form['user_interface']['wallet_tab'] = [
      '#title' => $this->t('Show wallets tab on canonical entity page'),
      '#description' => $this->t("Tab would show 'summary' view of each wallet owned by the entity. Otherwise show links to wallets canonical pages using the entity's display fields"),
      '#type' => 'checkbox',
      '#default_value' => $config->get('wallet_tab'),
      '#weight' => 3,
    ];
    $form['user_interface']['wallet_inex_tab'] = [
      '#title' => $this->t('Show income & expenditure tab on wallet page'),
      '#description' => $this->t("Alternatively include it in a wallet view mode"),
      '#type' => 'checkbox',
      '#default_value' => $config->get('wallet_inex_tab'),
      '#weight' => 3,
    ];
    $form['user_interface']['wallet_log_tab'] = [
      '#title' => $this->t('Show transaction log tab on wallet page'),
      '#description' => $this->t("Alternatively include it in a wallet view mode"),
      '#type' => 'checkbox',
      '#default_value' => $config->get('wallet_log_tab'),
      '#weight' => 3,
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    $vars = [
      'entity_types',
      'wallet_tab',
      'wallet_inex_tab',
      'wallet_log_tab',
      'autoadd',
    ];

    $config = $this->configFactory->getEditable('mcapi.settings');
    foreach ($vars as $var) {
      $config->set($var, $form_state->getValue($var));
    }
    $config->save();

    parent::submitForm($form, $form_state);

    Cache::invalidateTags(
      ['mcapi_wallet_values', 'mcapi_wallet_view', 'walletable_bundles']
    );

    $form_state->setRedirect('mcapi.admin');
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['mcapi.settings'];
  }

}
