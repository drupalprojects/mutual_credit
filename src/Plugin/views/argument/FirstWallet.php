<?php

/**
 * @file
 * Contains \Drupal\mcapi\Plugin\views\argument\FirstWallet.
 * Needed in income & expenditure view arguments
 */

namespace Drupal\mcapi\Plugin\views\argument;

use Drupal\views\Plugin\views\argument\ArgumentPluginBase;
use Drupal\user\Entity\User;
use Drupal\mcapi\Mcapi;

/**
 * Argument handler to convert a user id to that user's first wallet id
 *
 * @ViewsArgument("mcapi_first_wallet")
 */
class FirstWallet extends ArgumentPluginBase {


  public function query($group_by = FALSE) {
    $this->ensureMyTable();
    $wids = Mcapi::walletsOf(User::load($this->argument));
    $this->value = array_splice($wids, 0, 1);

    $placeholder = $this->placeholder();

    $this->query->addWhereExpression(
      0,
      "$this->tableAlias.payer = ".$placeholder." OR $this->tableAlias.payee = ".$placeholder,
      [$placeholder => $this->argument]
    );
  }
}
