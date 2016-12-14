<?php

namespace Drupal\mcapi\Plugin\Block;

use Drupal\mcapi\Exchange;
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

  protected $account;
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
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entityTypeManager, AccountInterface $currentUser, CurrentRouteMatch $current_route_match) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityTypeManager = $entityTypeManager;
    $this->currentUser = $currentUser;
    $this->routeMatch = $current_route_match;
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
      $container->get('current_route_match')
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
    // don't we need to call the parent?  See blockbase::access after alpha14.
    debug($this->getPluginDefinition(), 'check that block access is working');
    if ($this->configuration['user_source'] == static::USER_MODE_PROFILE) {
      if (!$this->routeMatch->getParameters()->has('user')) {
        return AccessResult::forbidden();
      }
    }
    return AccessResult::allowed();
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

    if ($this->configuration['user_source'] == static::USER_MODE_PROFILE) {
      // We already know the parameter bag has 'user' from blockAccess
      $this->account = $this->routeMatch->getParameter('user');
    }
    else {// Current user
      $this->account = $this->currentUser;
    }

    // Might want to move this to mcapi_exchanges.
    if (empty($this->configuration['curr_ids'])) {
      $this->currencies = Exchange::currenciesAvailableToUser($this->account);
    }
    else {
      $this->currencies = Currency::loadMultiple($this->configuration['curr_ids']);
    }
  }

}
