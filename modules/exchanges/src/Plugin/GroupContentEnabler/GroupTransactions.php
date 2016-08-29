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
 *   pretty_path_key = "transactions",
 *   enforced = FALSE
 * )
 */
class GroupTransactions extends GroupContentEnablerBase {
//
//  /**
//   * {@inheritdoc}
//   *
//   * @todo how does this conflict with the entity delete operation?
//   */
//  protected function deleteAccess(GroupContentInterface $group_content, AccountInterface $account) {
//    return GroupAccessResult::forbidden();
//  }
//
//  /**
//   * {@inheritdoc}
//   */
//  public function defaultConfiguration() {
//    $config = parent::defaultConfiguration();
//    $config['group_cardinality'] = 1;
//    $config['entity_cardinality'] = 1;
//    return $config;
//  }
//

}
