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
 * used in the wallet transactions view to determine the user's (first) wallet
 *
 * @ViewsArgument("mcapi_first_wallet")
 */
class FirstWallet extends ArgumentPluginBase {

  /**
   * {@inheritdoc}
   */
  public function query($group_by = FALSE) {
    $this->ensureMyTable();
    $this->value = Mcapi::firstWalletIdOfEntity(User::load($this->argument));
    $placeholder = $this->placeholder();
    if (isset($this->definition['field'])) {
      $field = $this->tableAlias .'.'.$this->definition['field'];
      $this->query->addWhereExpression(0, $field ."= ". $this->value);
    }
    else {
      $this->query->addWhereExpression(
        0,
        "$this->tableAlias.payer = ".$placeholder." OR $this->tableAlias.payee = ".$placeholder,
        [$placeholder => $this->value]
      );
    }
  }
}
