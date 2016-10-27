<?php

namespace Drupal\mcapi_exchanges\Plugin\views\argument;

use \Drupal\views\Plugin\views\argument\ArgumentPluginBase;

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
    $this->query->addWhereExpression(
      0,
      "$this->tableAlias.$this->realField = ".mcapi_exchanges_current_membership($this->argument)->getGroup()->id()
    );

  }

}
