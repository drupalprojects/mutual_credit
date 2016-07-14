<?php

namespace Drupal\exchanges\Plugin\entity_reference\selection;

use Drupal\Core\Database\Database;
use Drupal\Core\Database\Query\SelectInterface;
use Drupal\Core\Entity\Plugin\EntityReferenceSelection\SelectionBase;
use Drupal\user\Entity\User;

/**
 * Shows only users in a given exchange
 *
 * @EntityReferenceSelection(
 *   id = "user_by_exchange",
 *   label = @Translation("The current user's exchanges"),
 *   entity_types = {"user"},
 *   group = "default",
 *   weight = 1
 * )
 *
 * @todo Which group? How can the group module not do this already?
 *
 * @see \Drupal\group\Plugin\EntityReferenceSelection\GroupTypeRoleSelection
 */
class UserByExchange extends SelectionBase {

  /**
   * {@inheritdoc}
   */
  public function buildEntityQuery($match = NULL, $match_operator = 'CONTAINS') {
    $query = parent::buildEntityQuery($match, $match_operator);

    // The user entity doesn't have a label column.
    if (isset($match)) {
      $query->condition('name', $match, $match_operator);
    }
    return $query;
  }

  /**
   * {@inheritdoc}
   */
  public function entityQueryAlter(SelectInterface $query) {
    // Add the filter by role option.
    if (!empty($this->instance['settings']['handler_settings']['filter'])) {
      $filter_settings = $this->instance['settings']['handler_settings']['filter'];
      if ($filter_settings['type'] == 'role') {
        //testing
        $query->condition('role', $filter_settings['role']);
        return;
        //@todo use
        $tables = $query->getTables();
        $base_table = $tables['base_table']['alias'];
        $query->join('users_roles', 'ur', $base_table . '.uid = ur.uid');
        $query->condition('ur.rid', $filter_settings['role']);
      }
    }
  }

}
