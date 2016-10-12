<?php

namespace Drupal\mcapi_exchanges\Plugin\GroupContentEnabler;

use Drupal\mcapi\Mcapi;
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
 *   enforced = FALSE
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
    $type = $this->getEntityBundle();
    $operations = [];
    if ($group->hasPermission('create transactions', $account)) {
      foreach (mcapi_form_displays_load() as $displayEntity) {
        $wallet_id = Mcapi::firstWalletIdOfEntity(User::load($account->id()));
        $settings = $displayEntity->getThirdPartySettings('mcapi_forms');
        if (mcapi_forms_access_direction($account->id(), $settings['direction'])) {
          switch($settings['direction']) {
            case MCAPI_FORMS_DIR_INCOMING:
              $key = 'payer';
              break;
            case MCAPI_FORMS_DIR_OUTGOING:
              $key = 'payee';
              break;
            default:
              continue;
          }
          $operations["create-transaction"] = [
            'title' => $settings['title'],
            'url' => Url::fromUserInput($settings['path'], ['query' => [$key => $wallet_id]]),
            'weight' => 7,
          ];
        }
      }
    }
    if ($group->hasPermission('manage transactions', $account)) {
      // Not sure whether to display one or two links
      $operations["create-mass-transaction"] = [
        'title' => $this->t('Mass payment'),
        'url' => Url::fromRoute('mcapi.exchange.mass', ['group' => $group->id()]),
        'weight' => 7,
      ];
    }
    return $operations;
  }

  /**
   * {@inheritdoc}
   */
  public function getPermissions() {
    $permissions['manage transactions']['title'] = 'Manage transactions & wallets';
    $permissions['create transactions']['title'] = 'Register transactions';
    return $permissions;
  }

}
