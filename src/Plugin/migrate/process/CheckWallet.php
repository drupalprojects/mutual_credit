<?php

namespace Drupal\mcapi\Plugin\migrate\process;


use Drupal\mcapi\Storage\WalletStorage;
use Drupal\mcapi\Entity\Wallet;
use Drupal\user\Entity\User;
use Drupal\migrate\MigrateSkipRowException;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;

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
    $user_id = $value;
    if (!isset($this->map[$user_id])) {
      $user = User::load($user_id);
      if (!$user) {
        // Have to use the default wallet...so get user 1
        // NB this would be some other user in group situation!
        $user = User::load(1);
      }
      if ($wids = WalletStorage::walletsOf($user)) {
        $wid = reset($wids);
      }
      else {
        // Create a wallet for this user (should never be necessary)
        $wallet = Wallet::Create(['holder' => $user]);
        $wallet->save();
        $wid = $wallet->id();
      }
      $this->map[$user_id] = $wid;
    }

    // Because missing wallets have been replaced by user 1's and because imports
    // aren't validated, just check here that both wallets are different
    if ($destination_property == 'payee') {
      if ($row->getDestinationProperty('payer') == $this->map[$user_id]) {
        $xid = $row->getDestinationProperty('xid');
        throw new MigrateSkipRowException("Both wallets ". $this->map[$user_id] ." the same in transaction $xid");
      }
    }
    return $this->map[$user_id];
  }

}
