<?php

namespace Drupal\mcapi_cc;

use Drupal\mcapi\Entity\TransactionInterface;

/**
 * Manage communications with clearing central web service
 */
interface IntertradingInterface  {

  /**
   * Prepare this object with the Clearing Central login details
   *
   * @param string $curr_id
   * @param string $login
   * @param string $pass
   *
   * @return \Drupal\mcapi_cc\ClearingCentral
   */
  function init($curr_id, $login, $pass);

  /**
   * Send a transaction to Clearing Central server.
   *
   * @param \Drupal\mcapi\Entity\TransactionInterface $transaction
   * @param string $remote_exchange_id
   * @param string $remote_user_id
   *
   * @return array
   *   The remote transaction, returned from clearing central.
   */
  function send(TransactionInterface $transaction, $remote_exchange_id, $remote_user_id);

  /**
   * Handle an incoming instruction from clearing central to create a transaction.
   *
   * If the transaction is valid then save it otherwise return an error code.
   *
   * @param array $params
   *   data from clearing central about the proposed transaction
   *   'outgoing' means whether the transaction was outgoing from its source
   *
   * @return an http status code
   */
  function receive(&$params);

  /**
   * Get a friendly translated string from the response code.
   *
   * @param type $code
   *
   * @return string
   */
  static function responseLookup($code);


  /**
   * Get the url for an ajax search of exchange ids/names
   */
  static function nidSearchUrl();

  /**
   * Get the id of the currency which was put in using init.
   */
  static function getCurrencyId();
}