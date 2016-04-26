<?php

/**
 * @file
 * Contains \Drupal\mcapi\Plugin\migrate\process\CheckWallet.
 */

namespace Drupal\mcapi\Plugin\migrate\process;

use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;
use Drupal\mcapi\Entity\Wallet;
use Drupal\user\Entity\User;
use Drupal\mcapi\Mcapi;

/**
 * check the payer or payee user has a wallet and swop the uid for the wallet id
 *
 * @MigrateProcessPlugin(
 *   id = "check_wallet"
 * )
 */
class CheckWallet extends ProcessPluginBase {

  private $map = [];

  /**
   * {@inheritdoc}
   * Transform the user id from the d7 ledger to a wallet id for the d8 ledger, creating a wallet if necessary
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {

    $user_id = $row->getSourceProperty($destination_property);//bit ugly but what to do if $value is not set?
    if (!isset($this->map[$user_id])) {
      $user = User::load($user_id);
      $wids = Mcapi::walletsOf($user);
      if (empty($wids)) {
        $wallet = Wallet::Create(['holder' => $user]);
        $wallet->save();
        $wids = [$wallet->id()];
        drupal_set_message('new wallet created');
      }
      else drupal_set_message('existing wallet found');
      $this->map[$user_id] = reset($wids);
    }
    return $this->map[$user_id];
  }

}
