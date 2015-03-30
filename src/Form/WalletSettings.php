<?php

namespace Drupal\mcapi\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Url;
use Drupal\mcapi\Exchange;
use Drupal\mcapi\Mcapi;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\field\Entity\FieldConfig;
use Symfony\Component\DependencyInjection\ContainerInterface;

class WalletSettings extends ConfigFormBase {

  private $entityManager;
  /**
   * {@inheritdoc}
   */
  public function getFormID() {
    return 'mcapi_wallet_settings_form';
  }
    
  public function __construct(ConfigFactoryInterface $configFactory, $entityManager) {
    $this->setConfigFactory($configFactory);
    $this->entityManager = $entityManager;
  }
  
  static public function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('entity.manager')
    );
  }    
  

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->configFactory()->get('mcapi.wallets');

    $form['add_link_location'] = array(
      '#title' => t("Location of 'new wallet' link"),
      '#type' => 'radios',
      '#options' => array(//TODO change this to 2 checkboxes
        'local_action' => t("Local action on the owner's display page"),
        'summaries' => t('In the wallet summaries block'),
        'both' => t('Both')
      ),
      '#default_value' => $config->get('add_link_location'),
      '#weight' => 1,
    );
    //A wallet can be attached to any entity with an entity reference field pointing towards the exchange entity
    //OR to an exchange entity itself
    $link = \Drupal::l(
      'EntityOwnerInterface',
      Url::fromUri('https://api.drupal.org/api/drupal/core!modules!user!src!EntityOwnerInterface.php/interface/EntityOwnerInterface/8')
    );
    $form['entity_types'] = array(
      '#title' => t('Max number of wallets'),
      '#description' => t(
        "Wallets can be owned by any entity type which implements !interface and has an entity_references field to 'exchange' entities.", 
        ['!interface' => $link]
      ),
      '#type' => 'fieldset',
      '#weight' => 2,
      '#tree' => TRUE
    );

    foreach ($this->entityManager->getDefinitions() as $entity_type_id => $entity_type) {
      //tricky to know which entities to show here.
      if ($entity_type->isSubclassOf('\Drupal\Core\Entity\FieldableEntityInterface') && ($entity_type->isSubclassOf('\Drupal\User\EntityOwnerInterface') || in_array($entity_type_id, array('user')))) {
        $bundles = $this->entityManager->getBundleInfo($entity_type_id);
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
    $form['autoadd'] = [
      '#title' => t('Auto-create'),
      '#description' => t('One new wallet for each entity type above.') .' '.t('This is not retrospective'),
      '#type' => 'checkbox',
      '#default_value' => $config->get('autoadd'),
      '#weight' => 3,
    ];

    $permissions = Exchange::walletPermissions();

    $form['wallet_access'] = [
      '#title' => t('Default access of users to wallets'),
      '#description' => t('Determine which users can see, pay and charge from wallets by default.') .' '.
        t("If more than one box is checked, the first one will be the default for new wallets, and the owner of the wallet will be allowed to configure on their wallet 'edit tab'."),
      '#type' => 'details',
      '#weight' => 5
    ];
    $w = 0;
    foreach (Mcapi::walletOps() as $key => $description) {
      $form['wallet_access'][$key] = [
        '#title' => $description,
        '#type' => 'checkboxes',
        '#options' => $permissions,
        '#default_value' => $config->get($key),
        '#weight' => $w++,
      ];
      //TODO debug why these aren't working
      //should we remove the owner checkbox and let access control grant
      //all permissions to owner?
      $form['wallet_access'][WALLET_ACCESS_OWNER]['#default_value'] = TRUE;
      $form['wallet_access'][WALLET_ACCESS_OWNER]['#disabled'] = TRUE;
    }
    unset($form['wallet_access']['payin']['#options'][WALLET_ACCESS_ANY]);
    unset($form['wallet_access']['payout']['#options'][WALLET_ACCESS_ANY]);
    unset($form['wallet_access']['edit']['#options'][WALLET_ACCESS_ANY]);
    return parent::buildForm($form, $form_state);
  }


  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    //TODO check that none of the access is set to 'users' only
    //this would be very awkward for new wallets
    $values = $form_state->getValues();
    foreach (array_keys(Mcapi::walletOps()) as $op_name) {
      if (array_filter($values[$op_name]) == array('WALLET_ACCESS_USERS' => 'WALLET_ACCESS_USERS')) {
        $form_state->setErrorByName($op_name, t("'Named users' cannot be selected by itself"));
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $vals = $form_state->getValues('values');

    $this->configFactory->getEditable('mcapi.wallets')
      ->set('entity_types', $vals['entity_types'])
      ->set('add_link_location', $vals['add_link_location'])
      ->set('autoadd', $vals['autoadd'])
      ->set('details', array_filter($vals['details']))
      ->set('summary', array_filter($vals['summary']))
      ->set('payin', array_filter($vals['payin']))
      ->set('payout', array_filter($vals['payout']))
      ->save();

    parent::submitForm($form, $form_state);

    //TODO
    //Clear the FieldDefinitions cache for wallet entity, which uses these values as defaults

    $form_state->setRedirect('mcapi.admin');
  }
  
  protected function getEditableConfigNames() {
    return ['mcapi.wallets'];
  }
  
}



