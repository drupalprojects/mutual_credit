<?php

namespace Drupal\mcapi\Plugin\views\argument_default;

use Drupal\mcapi\Storage\WalletStorage;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Routing\CurrentRouteMatch;
use Drupal\views\Plugin\views\argument_default\ArgumentDefaultPluginBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * The fixed argument default handler.
 *
 * @ingroup views_argument_default_plugins
 *
 * @ViewsArgumentDefault(
 *   id = "route_wallet",
 *   title = @Translation("First wallet from route entity")
 * )
 */
class RouteWallets extends ArgumentDefaultPluginBase {

  protected $routeMatch;
  protected $walletStorage;

  /**
   * Constructor
   *
   * @param array $configuration
   * @param string $plugin_id
   * @param array $plugin_definition
   * @param CurrentRouteMatch $current_route_match
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, CurrentRouteMatch $current_route_match) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->routeMatch = $current_route_match;
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
   * @todo inject the service
   */
  public function getArgument() {
    // there's no validator in core either for ANY entity or for ANY
    // contentEntity or ANY Owned Entity, only for ONE given specific entityType
    // so this function needs to decide whether to return an argument.
    $wids = [];
    foreach ($this->routeMatch->getParameters()->all() as $entity) {
      if (Mcapi::maxWalletsOfBundle($entity->getEntityTypeId(), $entity->bundle())) {
        $wids = WalletStorage::walletsOf($entity);
      }
    }
    // @todo returning nothing means the view doesn't show - maybe throw a 404?
    // @see Drupal\mcapi_exchanges\Plugin\views\argument_default\RouteExchanges.
    return reset($wids);

  }

}
