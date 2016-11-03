<?php

namespace Drupal\mcapi_exchanges\Plugin\views\argument;

use \Drupal\views\Plugin\views\argument\ArgumentPluginBase;
use \Drupal\user\Entity\User;

/**
 * Pass the user id but filter by the content plugin user's exchange id
 *
 * @ingroup views_argument_handlers
 *
 * @ViewsArgument("user_exchange")
 */
class UserExchangeId extends ArgumentPluginBase {

  public function query($group_by = FALSE) {
    $this->ensureMyTable();
    $membership = group_exclusive_membership_get('exchange', (User::load($this->argument)));
    $this->query->addWhereExpression(
      0,
      "$this->tableAlias.$this->realField = ". $membership->getGroup()->id()
    );

  }

}
