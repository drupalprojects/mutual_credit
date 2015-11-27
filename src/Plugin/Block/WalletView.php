<?php

/**
 * @file
 * Contains \Drupal\mcapi\Plugin\Block\WalletView
 */

namespace Drupal\mcapi\Plugin\Block;

use Drupal\mcapi\Entity\Wallet;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Displays all the wallets of the current user OR the entity being viewed using
 * view mode 'mini'
 *
 * @Block(
 *   id = "mcapi_wallet_mini",
 *   admin_label = @Translation("Mini view of entity's wallets"),
 *   category = @Translation("Community Accounting")
 * )
 */

class WalletView extends BlockBase implements ContainerFactoryPluginInterface {

  protected $holder_entity;
  
  const MCAPIBLOCK_MODE_CONTEXT = 0;
  const MCAPIBLOCK_MODE_CURRENTUSER = 1;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, $entity_type_manager, $request) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityTypeManager = $entity_type_manager;
    
    if($this->configuration['user_source'] == SELF::MCAPIBLOCK_MODE_CONTEXT) {
      if ($request->attributes->has('_entity')) {
        $this->holder_entity = $request->attributes->get('_entity');
      }
    }
    else {
      $this->holder_entity = \Drupal::currentUser();
    }
  }

  static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('request_stack')->getCurrentRequest()
    );
  }
  
  /**
   * {@inheritdoc}
   * @todo check this isn't causing a problem with caching between different pages and users
   */
  public function blockAccess(AccountInterface $account) {
    return isset($this->holder_entity) ?  AccessResult::allowed(): AccessResult::forbidden();
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'user_source' => 0,
    ];
  }

  /**
   * {@inheritdoc}
   * 
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form = parent::blockForm($form, $form_state);
    $form['user_source'] = [
      '#title' => t('User'),
      '#type' => 'radios',
      '#options' => array(
        SELF::MCAPIBLOCK_MODE_CONTEXT => t('Show as part of profile being viewed'),
        SELF::MCAPIBLOCK_MODE_CURRENTUSER => t('Show for logged in user')
      ),
      '#default_value' => $this->configuration['user_source']
    ];
    return $form;
  }

  /**
   * Overrides \Drupal\block\BlockBase::blockSubmit().
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    parent::blockSubmit($form, $form_state);
    $values = $form_state->getValues();
    foreach ($values as $key => $val) {
      $this->configuration[$key] = $val;
    }
  }

  public function build() {
    $wids = $this->entityTypeManager
      ->getStorage('mcapi_wallet')
      ->filter(['holder' => $this->holder_entity]);
    $wallets = Wallet::loadMultiple($wids);
    return $this->entityTypeManager
      ->getViewBuilder('mcapi_wallet')
      ->viewMultiple($wallets, 'mini');
  }

}

