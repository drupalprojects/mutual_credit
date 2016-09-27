<?php

namespace Drupal\mcapi;

use Drupal\mcapi\Entity\Wallet;
use Drupal\Core\Session\AccountInterface;
use Drupal\user\Entity\User;
use Drupal\mcapi\Entity\Currency;
use Drupal\user\EntityOwnerInterface;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\mcapi_exchanges\Exchanges;

/**
 * Static class relating to one or multiple exchanges.
 */
class Exchange {

  /**
   * Load currencies for a given ownerentity.
   *
   * @param EntityOwnerInterface $entity
   *   The Owner Entity (not the wallet holder).
   *
   * @return Currency[]
   *   The currencies available to that entity.
   */
  public static function ownerEntityCurrencies(EntityOwnerInterface $entity = NULL) {
    return Self::MultipleExchanges() ?
      Exchanges::ownerEntityCurrencies($entity) :
      Currency::loadMultiple();
  }

  /**
   * Get a list of all the currencies currently in a wallet's scope.
   *
   * @param AccountInterface $account
   *   The user.
   *
   * @return CurrencyInterface[]
   *   Keyed by currency id.
   */
  public static function currenciesAvailableToUser(AccountInterface $account = NULL) {
    if (!$account) {
      $account = User::load(\Drupal::currentUser()->id());
    }
    if (!isset($account->currencies_available)) {
      $currencies = Self::MultipleExchanges() ?
        Exchanges::currenciesAvailableToUser($account) :
        Currency::loadMultiple();
      uasort($currencies, '\Drupal\mcapi\Mcapi::uasortWeight');
      $account->currencies_available = $currencies;
    }
    // Note this adhoc variable gives us no control over static or caching.
    return $account->currencies_available;
  }

  /**
   * Find out whether there is more than one exchange on this site.
   */
  private static function multipleExchanges() {
    static $multiple = NULL;
    if (!isset($multiple)) {
      $multiple = \Drupal::moduleHandler()->moduleExists('mcapi_exchanges');
    }
    return $multiple;
  }

  /**
   * Handle the deletion of the wallet's parent.
   *
   * If the wallet has no transactions it can be deleted. Otherwise make the
   * passed exchange the parent, must be found.
   *
   * @param ContentEntityInterface $holder
   *   The entity being deleted.
   */
  public static function orphan(ContentEntityInterface $holder) {
    foreach (Mcapi::walletsOf($holder, TRUE) as $wallet) {
      if (!$wallet->isVirgin()) {
        // Move the wallet.
        $new_holder_entity = \Drupal::moduleHandler()->moduleExists('mcapi_exchanges') ?
        // @todo this is a bit inelegant
          Exchanges::findNewHolder($holder) :
          User::load(1);
        $new_holder_entity = class_exists('\Drupal\mcapi_exchanges\Exchanges') ?
          Exchange::findNewHolder($holder) :
          User::load(1);
        $new_name = t(
          "Formerly @name's wallet: @label",
          ['@name' => $wallet->label(), '@label' => $wallet->label(NULL, FALSE)]
        );
        $wallet->set('name', $new_name)
          ->set('holder_entity_type', $new_holder_entity->getEntityTypeId())
          ->set('holder_entity_id', $new_holder_entity->id())
          ->save();
        // @note this implies the number of wallets an exchange can own to be unlimited.
        // or more likely that this max isn't checked during orphaning
        drupal_set_message(t(
          "@name's wallets are now owned by @entity_type @entity_label", [
            '@name' => $wallet->label(),
            '@entity_type' => $new_holder_entity->getEntityType()->getLabel(),
            // Todo I tried toLink but it doesn't render from here.
            '@entity_label' => $new_holder_entity->label(),
          ]
        ));
        \Drupal::logger('mcapi')->notice(
          'Wallet @wid was orphaned to @entitytype @id', [
            '@wid' => $wallet->id(),
            '@entitytype' => $new_holder_entity->getEntityTypeId(),
            '@id' => $new_holder_entity->id(),
          ]
        );
      }
      else {
        drupal_set_message(t('Deleted unused wallet @wallet_id', ['@wallet_id'  => $wallet->label()]));
        $wallet->delete();
        return;
      }
    }
  }

  /**
   * Identify an exchanges intertrading wallet.
   *
   * @param int $exchange_id
   *   The ID of the exchange.
   */
  public static function intertradingWalletId($exchange_id = NULL) {
    if (Self::multipleExchanges() && $exchange_id) {
      drupal_set_message('is this ever used?');
      // There might be a better name for this method, if there were many
      // methods that were filtering on the current user's exchange.
      $wid = Exchanges::getIntertradingWalletId($exchange_id);
    }
    else {
      $wids = \Drupal::entityQuery('mcapi_wallet')
        ->condition('payways', Wallet::PAYWAY_AUTO)
        ->execute();
      $wid = reset($wids);
    }
    return $wid;
  }

  /**
   * Identify a new parent entity for a wallet.
   */
  public static function findNewHolder(ContentEntityInterface $previous_holder) {
    return User::load(1);
  }

}
