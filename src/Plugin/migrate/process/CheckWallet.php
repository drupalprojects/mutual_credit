<?php

namespace Drupal\mcapi\Plugin\migrate\process;

use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;
use Drupal\mcapi\Storage\WalletStorage;
use Drupal\mcapi\Entity\Wallet;
use Drupal\user\Entity\User;

/**
 * Check the payer or payee user has a wallet and swop the uid for the wallet id.
 *
 * @MigrateProcessPlugin(
 *   id = "check_wallet"
 * )
 */
class CheckWallet extends ProcessPluginBase {

  private $map = [];

  /**
   * {@inheritdoc}
   * Transform the user id from the d7 ledger to a wallet id for the d8 ledger, creating a wallet if necessary.
   *
   * @todo inject
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {

    // Bit ugly but what to do if $value is not set?
    $user_id = $row->getSourceProperty($destination_property);
    if (!isset($this->map[$user_id])) {
      $user = User::load($user_id);
      $wids = WalletStorage::walletsOf($user);
      if (empty($wids)) {
        $wallet = Wallet::Create(['holder' => $user]);
        $wallet->save();
        $wids = [$wallet->id()];
        drupal_set_message('new wallet created');
      }
      else {
        drupal_set_message('existing wallet found');
      }
      $this->map[$user_id] = reset($wids);
    }
    return $this->map[$user_id];
  }

}
