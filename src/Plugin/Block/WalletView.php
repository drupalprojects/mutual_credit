<?php

namespace Drupal\mcapi\Plugin\Block;

use Drupal\mcapi\Storage\WalletStorage;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Displays all the wallets of an entity.
 *
 * Entity being either the current user OR the entity being viewed. Shows the
 * wallet view mode 'mini'.
 *
 * @Block(
 *   id = "mcapi_wallet_mini",
 *   admin_label = @Translation("Mini view of entity's wallets"),
 *   category = @Translation("Community Accounting")
 * )
 */
class WalletView extends BlockBase implements ContainerFactoryPluginInterface {

  protected $holderEntity;

  const MCAPIBLOCK_MODE_CONTEXT = 0;
  const MCAPIBLOCK_MODE_CURRENTUSER = 1;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, $entity_type_manager, $current_user, $route_match, $request) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $entity_type_manager, $current_user, $route_match);

    if ($this->configuration['user_source'] == SELF::MCAPIBLOCK_MODE_CONTEXT) {
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
   *
   * @todo check this isn't causing a problem with caching between different pages and users
   */
  public function blockAccess(AccountInterface $account) {
    return $this->holderEntity ? AccessResult::allowed() : AccessResult::forbidden('No wallet holder in page context');
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form = parent::blockForm($form, $form_state);
    unset($form['currencies']);
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    parent::blockSubmit($form, $form_state);
    $values = $form_state->getValues();
    unset($values['curr_ids']);
    foreach ($values as $key => $val) {
      $this->configuration[$key] = $val;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    return $this->entityTypeManager
      ->getViewBuilder('mcapi_wallet')
      ->viewMultiple(
        WalletStorage::walletsOf($this->holderEntity, TRUE),
        'mini'
      );
  }

}
