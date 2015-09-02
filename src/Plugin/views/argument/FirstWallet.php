<?php

/**
 * @file
 * Contains \Drupal\mcapi\Plugin\views\argument\FirstWallet.
 */

namespace Drupal\mcapi\Plugin\views\argument;

use Drupal\views\Plugin\views\argument\ArgumentPluginBase;
use Drupal\user\Entity\User;

/**
 * Argument handler to convert a user id to that user's first wallet it
 *
 * @ViewsArgument("mcapi_first_wallet")
 */
class FirstWallet extends ArgumentPluginBase {


  public function query($group_by = FALSE) {
    $this->ensureMyTable();

    $wallet_ids = \Drupal::entityManager()->getStorage('mcapi_wallet')->filter(['holder' => User::load($this->argument)]);
    $this->value = array_splice($wallet_ids, 0, 1);

    $placeholder = $this->placeholder();
      
    $this->query->addWhereExpression(
      0, 
      "$this->tableAlias.$this->realField = ".$placeholder,
      [$placeholder => $this->argument]
    );
  }
}
