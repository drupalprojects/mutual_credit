<?php

/**
 * @file
 *
 * @temp until group module creates these tasks automatically
 */

namespace Drupal\mcapi_exchanges;

use Drupal\Component\Plugin\Derivative\DeriverBase;
use Drupal\Core\Plugin\Discovery\ContainerDeriverInterface;
use Drupal\group\Plugin\GroupContentEnablerManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines dynamic local tasks for group content pages.
 */
class GroupContentDynamicDeriver extends DeriverBase implements ContainerDeriverInterface {

  /**
   * The group content enabler plugin manager.
   *
   * @var \Drupal\group\Plugin\GroupContentEnablerManagerInterface
   */
  protected $pluginManager;

  /**
   * Constructs a new GroupContentDynamicDeriver.
   *
   * @param \Drupal\group\Plugin\GroupContentEnablerManagerInterface $plugin_manager
   *   The group content enabler plugin manager.
   */
  public function __construct(GroupContentEnablerManagerInterface $plugin_manager) {
    $this->pluginManager = $plugin_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, $base_plugin_id) {
    return new static(
      $container->get('plugin.manager.group_content_enabler')
    );
  }

  /**
   * {@inheritdoc}
   *
   * @todo
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    // Retrieve all possible collection route names from all installed plugins.
    foreach ($this->pluginManager->getInstalled() as $plugin_id => $plugin) {
      /** @var \Drupal\group\Plugin\GroupContentEnablerInterface $plugin */
      $def = $plugin->getPluginDefinition();
      $this->derivatives['group.'.$plugin_id] = [
        'title' => $def['label'],
        'base_route' => 'entity.group.canonical',
        'route_name'  => 'entity.group_content.'.$def['id'].'.collection',
        'weight' => 10, // @todo
      ];
    }
    return $this->derivatives;
  }

}
