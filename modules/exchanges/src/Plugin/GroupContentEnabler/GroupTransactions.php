<?php

namespace Drupal\mcapi_exchanges\Plugin\GroupContentEnabler;

use Drupal\group\Access\GroupAccessResult;
use Drupal\group\Entity\GroupContentInterface;
use Drupal\group\Plugin\GroupContentEnablerBase;
use Drupal\Core\Session\AccountInterface;

/**
 * Provides a content enabler for transactions.
 *
 * @GroupContentEnabler(
 *   id = "transactions",
 *   label = @Translation("Transactions"),
 *   description = @Translation("Adds transactions to groups."),
 *   entity_type_id = "mcapi_transaction",
 *   path_key = "transactions",
 *   enforced = FALSE
 * )
 */
class GroupTransactions extends GroupContentEnablerBase {

  /**
   * {@inheritdoc}
   */
  public function getPaths() {
    // The parent gets more paths than we can use.
    return [
      'collection' => "/group/{group}/transactions",
      'canonical' => "/group/{group}/transactions/{group_content}",
    ];
  }

  /**
   * {@inheritdoc}
   *
   * @todo how does this conflict with the entity delete operation?
   */
  protected function deleteAccess(GroupContentInterface $group_content, AccountInterface $account) {
    return GroupAccessResult::forbidden();
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    $config = parent::defaultConfiguration();
    $config['group_cardinality'] = 1;
    $config['entity_cardinality'] = 1;
    return $config;
  }

  public function getLocalActions() {
    $actions = [];
    // Can't inherit from the parent in alpha 7 because it adds add-form
    // without checking whether it is used.
    return $actions;
  }

}
