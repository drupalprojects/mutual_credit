<?php

namespace Drupal\mcapi_exchanges\Plugin\GroupContentEnabler;

use Drupal\group\Access\GroupAccessResult;
use Drupal\group\Entity\GroupInterface;
use Drupal\group\Entity\GroupContentInterface;
use Drupal\group\Plugin\GroupContentEnablerBase;
use Drupal\Core\Url;
use Drupal\Core\Session\AccountInterface;

/**
 * Provides a content enabler for transactions.
 *
 * @GroupContentEnabler(
 *   id = "currencies",
 *   label = @Translation("Currencies"),
 *   description = @Translation("Adds currencies to groups."),
 *   entity_type_id = "mcapi_currency",
 *   pretty_path_key = "currencies",
 *   enforced = FALSE
 * )
 *
 * @deprecated because groupContent can only be ContentEntities
 */
class GroupCurrency extends GroupContentEnablerBase {

  /**
   * {@inheritdoc}
   */
  public function getPermissions() {
    $permissions['manage currencies']['title'] = 'Manage currencies';
    return $permissions;
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
  public function getGroupOperations(GroupInterface $group) {
    $account = \Drupal::currentUser();
    $type = $this->getEntityBundle();
    $operations = [];
    if ($group->hasPermission('create currencies', $account)) {
      foreach (mcapi_form_displays_load() as $displayEntity) {
        $operations["create-transaction"] = [
          'title' => $this->t('Manage currencies'),
          'url' => Url::fromRoute('entity.mcapi_currency.collection'),
          'weight' => 9,
        ];
      }
    }
    return $operations;
  }

}
