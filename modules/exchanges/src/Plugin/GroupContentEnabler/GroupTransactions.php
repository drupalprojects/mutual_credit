<?php

namespace Drupal\mcapi_exchanges\Plugin\GroupContentEnabler;

use Drupal\mcapi\Storage\WalletStorage;
use Drupal\group\Access\GroupAccessResult;
use Drupal\group\Entity\GroupInterface;
use Drupal\group\Entity\GroupContentInterface;
use Drupal\group\Plugin\GroupContentEnablerBase;
use Drupal\Core\Url;
use Drupal\Core\Session\AccountInterface;
use Drupal\user\Entity\User;

/**
 * Provides a content enabler for transactions.
 *
 * @GroupContentEnabler(
 *   id = "transactions",
 *   label = @Translation("Transactions"),
 *   description = @Translation("Adds transactions to groups."),
 *   entity_type_id = "mcapi_transaction",
 *   pretty_path_key = "transactions",
 *   enforced = TRUE
 * )
 */
class GroupTransactions extends GroupContentEnablerBase {

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
  public function getGroupOperations(GroupInterface $group) {
    $account = \Drupal::currentUser();
    $operations = [];
    if ($group->hasPermission('manage transactions', $account)) {
      // Not sure whether to display one or two links
      $operations["create-mass-transaction"] = [
        'title' => $this->t('Mass payment'),
        'url' => Url::fromRoute('mcapi.masspay'),
        'weight' => 7,
      ];
    }
    if (\Drupal::moduleHandler()->moduleExists('mcapi_forms')) {
      if ($group->hasPermission('create transactions', $account)) {
        foreach(_mcapi_forms_quick_links() as $data) {
          $operations["create-transaction"] = [
           'title' => $data['title'],
           'url' => $data['url'],
           'weight' => 7,
         ];
        }
      }
    }
    return $operations;
  }

  /**
   * {@inheritdoc}
   */
  public function getPermissions() {
    $permissions['manage transactions']['title'] = 'Manage transactions & wallets';
    $permissions['create transactions']['title'] = 'Register transactions';
    $permissions['view transactions']['title'] = 'View transactions';
    return $permissions;
  }

}
