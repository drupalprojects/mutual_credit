<?php

/**
 * @file
 *
 * Contains \Drupal\mcapi\Exchange.
 * The purpose of this class is to contain all the functions that vary when module
 * mcapi_exchanges is installed.
 * In which case this is class is replaced by \Drupal\mcapi_exchanges\Exchanges
 *
 */

namespace Drupal\mcapi;

use Drupal\user\Entity\User;
use Drupal\mcapi\Entity\Currency;
use Drupal\user\EntityOwnerInterface;
use Drupal\Core\Entity\ContentEntityInterface;

use Drupal\mcapi_exchanges\Exchanges;


class Exchange {

  /**
   * Load currencies for a given ownerentity
   *
   * @param EntityOwnerInterface $entity
   *
   * @return Currency[]
   */
  public static function ownerEntityCurrencies(EntityOwnerInterface $entity = NULL) {
    return Self::MultipleExchanges() ?
      Exchanges::ownerEntityCurrencies($entity) :
      Currency::loadMultiple();
  }

  /**
   * get a list of all the currencies currently in a wallet's scope, ordered by weight
   *
   * @param \Drupal\user\UserInterface $account
   *
   * @return CurrencyInterface[]
   *   keyed by currency id
   *
   */
  public static function currenciesAvailableToUser(\Drupal\user\UserInterface $account = NULL) {
    if (!$account) {
      $account = User::load(\Drupal::currentUser()->id());
    }
    if (!isset($account->currencies_available)) {
      $currencies = Self::MultipleExchanges() ?
        Exchanges::currenciesAvailableToUser($account) :
        Currency::loadMultiple();

      if (!$currencies) {
        debug('Why would there be no currencies availabe to user '.$account->id());
      }
      uasort($currencies, '\Drupal\mcapi\Mcapi::uasortWeight');
      $account->currencies_available = $currencies;
    }
    //note this adhoc variable gives us no control over static or caching
    return $account->currencies_available;
  }


  private static function multipleExchanges() {
    static $multiple = NULL;
    if (!isset($multiple)) {
      $multiple = \Drupal::moduleHandler()->moduleExists('mcapi_exchanges');
    }
    return $multiple;
  }


  /**
   * Handle the deletion of the wallet's parent
   * If the wallet has no transactions it can be deleted
   * Otherwise make the passed exchange the parent, must be found.
   *
   * @param ContentEntityInterface $holder
   */
  public static function orphan(ContentEntityInterface $holder) {
    $entityTypeManager = \Drupal::entityTypeManager();
    foreach (Mcapi::walletsOf($holder, TRUE) as $wallet) {
      if ($wallet->isUsed()) {
        //move the wallet
        $new_holder_entity = \Drupal::moduleHandler()->moduleExists('mcapi_exchanges') ?
          Exchanges::findNewHolder($holder) : //@todo this is a bit inelegant
          User::load(1);
        $new_holder_entity = class_exists('\Drupal\mcapi_exchanges\Exchanges') ?
          Exchange::findNewHolder($holder) :
          User::load(1);
        $new_name = t(
          "Formerly @name's wallet: @label", ['@name' => $wallet->label(), '@label' => $wallet->label(NULL, FALSE)]
        );
        $wallet->set('name', $new_name)
          ->set('holder_entity_type', $new_holder_entity->getEntityTypeId())
          ->set('holder_entity_id', $new_holder_entity->id())
          ->save();
        //@note this implies the number of wallets an exchange can own to be unlimited.
        //or more likely that this max isn't checked during orphaning
        drupal_set_message(t(
          "@name's wallets are now owned by @entity_type @entity_label", [
          '@name' => $wallet->label(),
          '@entity_type' => $new_holder_entity->getEntityType()->getLabel(),
            //todo I tried toLink but it doesn't render from here
          '@entity_label' => $new_holder_entity->label()
            ]
        ));
        \Drupal::logger('mcapi')->notice(
          'Wallet @wid was orphaned to @entitytype @id', [
          '@wid' => $wallet->id(),
          '@entitytype' => $new_holder_entity->getEntityTypeId(),
          '@id' => $new_holder_entity->id()
          ]
        );
      }
      else {
        drupal_set_message('deleting unused wallet '.$wallet->label());
        $wallet->delete();
        return;
      }
    }
  }

  static function intertradingWalletId() {
    $query = \Drupal::entityQuery('mcapi_wallet')
      ->condition('payways', \Drupal\mcapi\Entity\Wallet::PAYWAY_AUTO);
    if (Self::multipleExchanges()) {
      //there might be a better name for this method,
      //if there were many methods that were filtering on the current user's exchange
      Exchanges::intertradingWalletId($query);
    }
    $wids = $query->execute();
    return reset($wids);
  }
}
