<?php

/**
 * @file
 * Contains \Drupal\mcapi\Plugin\Derivative\WalletLocalAction.
 */

namespace Drupal\mcapi\Plugin\Derivative;

use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Component\Plugin\Derivative\DeriverBase;
use Drupal\Core\Plugin\Discovery\ContainerDeriverInterface;
use Drupal\Core\Routing\RouteProviderInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides local action definitions to create a wallet for all entities of types configured.
 */
class WalletLocalAction extends DeriverBase implements ContainerDeriverInterface {

  /**
   * The route provider.
   *
   * @var \Drupal\Core\Routing\RouteProviderInterface
   */
  protected $routeProvider;

  /**
   * The entity manager
   *
   * @var \Drupal\Core\Entity\EntityManagerInterface
   */
  protected $entityManager;

  /**
   * The translation manager service.
   *
   * @var \Drupal\Core\StringTranslation\TranslationInterface
   */
  protected $translationManager;

  /**
   * Creates a WalletLocalAction object.
   *
   * @param \Drupal\Core\Routing\RouteProviderInterface $route_provider
   *   The route provider.
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The entity manager.
   * @param \Drupal\Core\StringTranslation\TranslationInterface $translation_manager
   *   The translation manager.
   */
  public function __construct(RouteProviderInterface $route_provider, EntityManagerInterface $entity_manager) {
    $this->routeProvider = $route_provider;
    $this->entityManager = $entity_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, $base_plugin_id) {
    return new static(
      $container->get('router.route_provider'),
      $container->get('entity.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    $this->derivatives = array();
    $config = \Drupal::config('mcapi.wallets');
    if ($config->get('add_link_location') != 'summaries') {
      $types = $config->get('entity_types');
      foreach((array)$types as $entity_bundle => $max) {
        list($entity_type, $bundle) = explode(':', $entity_bundle);
        //assumes that bundle names are unique
        $this->derivatives["mcapi.wallet.add.".$bundle.'.action'] = array(
          'id' => "mcapi.wallet.add.".$bundle.'.action',
          'route_name' => "mcapi.wallet.add.$bundle",//taken from the routesubscriber
          'title' => t('Add Wallet'),
          'appears_on' => array(
            $this->entityManager->getDefinition($entity_type, TRUE)->getLinkTemplate('canonical')
          ),
        );
      }
      foreach ($this->derivatives as &$entry) {
        $entry += $base_plugin_definition;
      }
    }
    return $this->derivatives;
  }

  public function getDerivativeDefinition($derivative_id, $base_plugin_definition){
    //so far this hasn't been called, I don't know when it is used
    die('\Drupal\mcapi\Plugin\Derivative\WalletLocalAction::getDerivativeDefinition');
  }
}
