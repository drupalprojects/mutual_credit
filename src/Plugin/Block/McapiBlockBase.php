<?php

namespace Drupal\mcapi\Plugin\Block;

use Drupal\mcapi\Mcapi;
use Drupal\mcapi\Currency;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Routing\CurrentRouteMatch;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Base block class for user transaction data.
 */
class McapiBlockBase extends BlockBase implements ContainerFactoryPluginInterface {

  const USER_MODE_CURRENT = 1;
  const USER_MODE_PROFILE = 0;

  protected $entityTypeManager;
  protected $currentUser;
  protected $routeMatch;
  protected $holderEntity;

  protected $currencies;

  /**
   * Constructor
   *
   * @param array $configuration
   * @param type $plugin_id
   * @param type $plugin_definition
   * @param EntityTypeManagerInterface $entityTypeManager
   * @param AccountInterface $currentUser
   * @param CurrentRouteMatch $current_route_match
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entityTypeManager, AccountInterface $currentUser, CurrentRouteMatch $current_route_match, $request) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityTypeManager = $entityTypeManager;
    $this->currentUser = $currentUser;
    $this->routeMatch = $current_route_match;

    if ($this->configuration['user_source'] == SELF::USER_MODE_PROFILE) {
      if ($request->attributes->has('_entity')) {
        $this->holderEntity = $request->attributes->get('_entity');
      }
    }
    else {
      $this->holderEntity = \Drupal::currentUser();
    }
  }

  /**
   * Injection.
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('current_user'),
      $container->get('current_route_match'),
      $container->get('request_stack')->getCurrentRequest()
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'curr_ids' => [],
      'user_source' => 0,
    ];
  }

  /**
   * {@inheritdoc}
   *
   * In profile mode, hide the block if we are not on a profile page.
   */
  public function blockAccess(AccountInterface $account) {
    return $this->holderEntity instanceof \Drupal\Core\Session\AccountProxyInterface ?
      AccessResult::forbidden('Settings for this block require a user context.') :
      AccessResult::allowed();
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    parent::blockForm($form, $form_state);
    $form['curr_ids'] = [
      '#title' => t('Currencies'),
    // There must be a string in Drupal that says that already?
      '#description' => t('Select none to select all'),
      '#title_display' => 'before',
      '#type' => 'mcapi_currency_select',
      '#default_value' => $this->configuration['curr_ids'],
      '#options' => Mcapi::entityLabelList('mcapi_currency', array('status' => TRUE)),
      '#multiple' => TRUE,
    ];
    $form['user_source'] = [
      '#title' => t('User'),
      '#type' => 'radios',
      '#options' => array(
        static::USER_MODE_PROFILE => t('Show as part of profile being viewed'),
        static::USER_MODE_CURRENT => t('Show for logged in user'),
      ),
      '#default_value' => $this->configuration['user_source'],
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    parent::blockSubmit($form, $form_state);
    $values = $form_state->getValues();
    foreach ($values as $key => $val) {
      $this->configuration[$key] = $val;
    }
     $this->configuration['curr_ids'] = array_filter($this->configuration['curr_ids']);
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    // Might want to move this to mcapi_exchanges.
    if (empty($this->configuration['curr_ids'])) {
      $user = User::load($this->holderEntity->id());
      $this->currencies = mcapi_currencies_available($user);
    }
    else {
      $this->currencies = Currency::loadMultiple($this->configuration['curr_ids']);
    }
  }

}
