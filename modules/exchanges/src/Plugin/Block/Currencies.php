<?php

namespace Drupal\mcapi_exchanges\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Shows all the currencies available to the group
 *
 * @Block(
 *   id = "mcapi_exchange_currencies",
 *   admin_label = @Translation("Currencies in the group"),
 *   category = @Translation("Community Accounting"),
 *   context = {
 *     "exchange" = @ContextDefinition("entity:group", required = TRUE)
 *   }
 * )
 */
class Currencies extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition
    );
  }

  /**
   * Construction.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public function label() {
    return 'blah';
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $build['#cache']['contexts'] = ['route', 'user'];
    if (($group = $this->getContextValue('exchange')) && $group->id()) {
      return [
        '#theme' => 'currencies',
        '#currencies' => $group->currencies->referencedEntities()
      ];
    }
    return [];
  }

}
