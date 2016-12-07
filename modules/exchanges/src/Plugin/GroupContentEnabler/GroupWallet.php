<?php

namespace Drupal\mcapi_exchanges\Plugin\GroupContentEnabler;

use Drupal\group\Access\GroupAccessResult;
use Drupal\group\Entity\GroupContentInterface;
use Drupal\group\Plugin\GroupContentEnablerBase;
use Drupal\Core\Session\AccountInterface;

/**
 * Provides a content enabler for wallets.
 *
 * @GroupContentEnabler(
 *   id = "wallet",
 *   label = @Translation("Wallet"),
 *   description = @Translation("Adds wallets to groups."),
 *   entity_type_id = "mcapi_wallet",
 *   pretty_path_key = "wallets",
 *   enforced = TRUE
 * )
 *
 */
class GroupWallet extends GroupContentEnablerBase {

  /**
   * {@inheritdoc}
   */
  protected function deleteAccess(GroupContentInterface $group_content, AccountInterface $account) {
    return GroupAccessResult::forbidden();
  }

}
