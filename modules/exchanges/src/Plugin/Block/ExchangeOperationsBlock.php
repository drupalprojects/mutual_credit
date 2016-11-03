<?php

namespace Drupal\mcapi_exchanges\Plugin\Block;

use Drupal\group\Access\GroupAccessResult;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Block\TitleBlockPluginInterface;

/**
 * Provides a block with operations the user can perform in their exchange.
 *
 * @Block(
 *   id = "exchange_operations",
 *   admin_label = @Translation("Exchange operations")
 * )
 */
class ExchangeOperationsBlock extends BlockBase  {

  /**
   * @var \Drupal\group\Entity\GroupInterface $group
   */
  protected $exchange;

  public function __construct(array $configuration, $plugin_id, $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    // We could also pull this from context service
    $this->exchangeMembership = group_exclusive_membership_get('exchange');
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $build = [];
    $build['#cache']['contexts'] = ['group.type', 'group_membership.roles.permissions'];
    $exchange = $this->exchangeMembership->getGroup();
    $build['#exchange'] = $exchange;
    $build['#title'] = $this->t('Operations for '.$exchange->label());
    $links = [];
    // Retrieve the operations from the installed content plugins.
    foreach ($exchange->getGroupType()->getInstalledContentPlugins() as $plugin) {
      /** @var \Drupal\group\Plugin\GroupContentEnablerInterface $plugin */
      $links += $plugin->getGroupOperations($exchange);
    }
    unset($links['group-leave'], $links['group-join']);
    if ($links) {
      // We bury this inside 'list' otherwise the list #title and the block #title are confused.
      uasort($links, '\Drupal\Component\Utility\SortArray::sortByWeightElement');
      $build['list']['#theme'] = 'item_list';
      $build['list']['#list_type'] = 'ul';
      foreach ($links as $data) {
        $build['list']['#items'][] = [
          '#type'=> 'link',
          '#title' => $data['title'],
          '#url' => $data['url']
        ];
      }
    }
    // If no exchange was found, cache the empty result on the route.
    return $build;
  }

  protected function blockAccess(AccountInterface $account) {
    return GroupAccessResult::allowedif(!is_null($this->exchangeMembership))->cachePerUser();
  }

}
