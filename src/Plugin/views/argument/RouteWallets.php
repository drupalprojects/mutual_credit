<?php

namespace Drupal\mcapi\Plugin\views\argument;

use Drupal\mcapi\Mcapi;
use Drupal\mcapi\Storage\WalletStorage;
use Drupal\views\Plugin\views\argument\ArgumentPluginBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Argument handler to get the first wallet of the entity given in the route.
 *
 * @ingroup views_argument_handlers
 *
 * @ViewsArgument("route_wallet")
 *
 * @todo test whether this is used and needed in relation to the very similar @ViewsArgumentDefault
 */
class RouteWallets extends ArgumentPluginBase implements ContainerFactoryPluginInterface {

  /**
   * The route match.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;
  protected $entityTypeManager;

  /**
   * Constructor.
   *
   * @param array $configuration
   * @param string $plugin_id
   * @param array $plugin_definition
   * @param RouteMatchInterface $current_route_match
   * @param EntityTypeManagerInterface $entity_type_manager
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, RouteMatchInterface $current_route_match, EntityTypeManagerInterface $entity_type_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->routeMatch = $current_route_match;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('current_route_match'),
      $container->get('entity_type.manager')
    );
  }


  /**
   * {@inheritdoc}
   */
  public function setArgument($arg) {
    // there's no validator in core either for ANY entity or for ANY
    // contentEntity or ANY Owned Entity, only for ONE given specific entityType
    // so this function needs to decide whether to return an argument.
    foreach ($this->routeMatch->getParameters()->all() as $name => $val) {
      if ($def = $this->entityTypeManager->getDefinition($name, FALSE)) {
        if (Mcapi::maxWalletsOfBundle($name, $def->getKey('bundle'))) {
          $entity = $this->entityTypeManager->getStorage($name)->load($val);
          $wids = WalletStorage::walletsOf($entity);
          $this->argument = reset($wids);
          $this->argument_validated = TRUE;
          return TRUE;
        }
      }
    }
    return parent::setArgument($arg);
  }



}
