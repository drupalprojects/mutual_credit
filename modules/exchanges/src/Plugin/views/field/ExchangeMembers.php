<?php

namespace Drupal\mcapi_exchanges\Plugin\views\field;

use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\ResultRow;
use Drupal\group\GroupMembership;

/**
 * Field handler to get the member count from the exchange entity.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("exchange_members")
 */
class ExchangeMembers extends FieldPluginBase {

  /**
   * {@inheritdoc}
   */
  public function render(ResultRow $values) {
    $group = $this->getEntity($values);
    // Rather cumbersome.
    return count(GroupMembership::loadByGroup($group));
  }

}
