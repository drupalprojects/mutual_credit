<?php

namespace Drupal\mcapi\Plugin\views\argument;

use Drupal\mcapi\Mcapi;
use Drupal\views\Plugin\views\argument\ArgumentPluginBase;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Argument handler to get the first wallet of the entity given in the route.
 *
 * @ingroup views_argument_handlers
 *
 * @ViewsArgument("route_wallet")
 */
class RouteWallets extends ArgumentPluginBase implements ContainerFactoryPluginInterface {

  /**
   * The route match.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

  /**
   * Constructor.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The route match.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, RouteMatchInterface $route_match) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->routeMatch = $route_match;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('current_route_match')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function query($group_by = FALSE) {
    $this->ensureMyTable();
  }


  /**
   * {@inheritdoc}
   */
  public function setArgument($entity_id) {
    // there's no validator in core either for ANY entity or for ANY
    // contentEntity or ANY Owned Entity, only for ONE given specific entityType
    // so this function needs to decide whether to return an argument.
    foreach ($this->routeMatch->getParameters()->all() as $name => $val) {
      if ($def = \Drupal::entityTypeManager()->getDefinition($name, FALSE)) {
        if (Mcapi::maxWalletsOfBundle($name, $def->getKey('bundle'))) {
          $entity = \Drupal::entityTypeManager()->getStorage($name)->load($val);
          $wids = Mcapi::walletsOf($entity);
          $arg = reset($wids);
          $this->argument_validated = TRUE;
          return TRUE;
        }
      }
    }
  }



}
