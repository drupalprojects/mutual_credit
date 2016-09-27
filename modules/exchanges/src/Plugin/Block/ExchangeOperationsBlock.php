<?php

namespace Drupal\mcapi_exchanges\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;

/**
 * Provides a block with operations the user can perform in their exchange.
 *
 * @Block(
 *   id = "exchange_operations",
 *   admin_label = @Translation("Exchange operations")
 * )
 */
class ExchangeOperationsBlock extends BlockBase {

  /**
   * @var \Drupal\group\Entity\GroupInterface $group
   */
  protected $membership;

  public function __construct(array $configuration, $plugin_id, $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->membership = mcapi_exchanges_current_membership();
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $build = [];
    $build['#cache']['contexts'] = ['group.type', 'group_membership.roles.permissions'];

    $links = [];
    $exchange = $this->membership->getGroup();
    // Retrieve the operations from the installed content plugins.
    foreach ($exchange->getGroupType()->getInstalledContentPlugins() as $plugin) {
      /** @var \Drupal\group\Plugin\GroupContentEnablerInterface $plugin */
      $links += $plugin->getGroupOperations($exchange);
    }
if (!$links)die('No links found in exchange operations block');
    unset($links['group-leave'], $links['group-join']);
    if ($links) {
      uasort($links, '\Drupal\Component\Utility\SortArray::sortByWeightElement');
      $build['#theme'] = 'item_list';
      $build['#list_type'] = 'ul';
      foreach ($links as $data) {
        $build['#items'][] = [
          '#type'=> 'link',
          '#title' => $data['title'],
          '#url' => $data['url']
        ];
      }
    }
    // If no group was found, cache the empty result on the route.
    return $build;
  }

  protected function blockAccess(AccountInterface $account) {
    return AccessResult::allowedif(!is_null($this->membership))->cachePerUser();
  }

}
