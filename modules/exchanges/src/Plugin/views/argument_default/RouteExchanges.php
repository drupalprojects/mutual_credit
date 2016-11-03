<?php

namespace Drupal\mcapi_exchanges\Plugin\views\argument_default;

use Drupal\mcapi\Mcapi;
use Drupal\views\Plugin\views\argument_default\ArgumentDefaultPluginBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Return as a views argument, the exchanges the viewed entity is in.
 *
 * @ingroup views_argument_default_plugins
 *
 * @ViewsArgumentDefault(
 *   id = "route_exchanges",
 *   title = @Translation("The exchanges the viewed entity is in")
 * )
 */
class RouteExchanges extends ArgumentDefaultPluginBase {

  protected $routeMatch;

  /**
   * Constructs a PluginBase object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, $route_match) {
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
   * Return the default argument.
   *
   * @todo inject the Routematch service
   */
  public function getArgument() {
    // there's no validator in core either for ANY entity or for ANY contentEntity or ANY Owned Entity
    // only for ONE given specific entityType
    // so this function needs to decide whether to return an argument.
    $ids = [];
    foreach ($this->routeMatch->getParameters()->all() as $entity) {
      if (Mcapi::maxWalletsOfBundle($entity->getEntityTypeId(), $entity->bundle())) {
        foreach (GroupContent::loadByEntity($entity) as $groupContent) {
          if ($groupContent->bundle() == 'exchange-group_membership') {
            $exchange_ids[] = $groupContent->getGroup()->id();
          }
        }
        break;
      }
    }
    return implode('+', $exchange_ids);
    // Returning nothing means the view doesn't show.
  }

}
