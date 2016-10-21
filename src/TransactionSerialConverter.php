<?php

namespace Drupal\mcapi;

use Drupal\mcapi\Entity\Transaction;
use Drupal\Core\ParamConverter\EntityConverter;
use Symfony\Component\Routing\Route;

/**
 * Provides upcasting for a transaction entity to be used in the Views UI.
 *
 * Example:
 *
 * path: '/transaction/{transaction_serial}'
 * options:
 *   parameters:
 *     mcapi_transaction:
 *       serial: TRUE
 *
 * The value for {view} will be converted to a view entity prepared for the
 * Views UI and loaded from the views temp store, but it will not touch the
 * value for {bar}.
 * If transaction_serial is 0, the transaction will be pulled from the tempstore
 */
class TransactionSerialConverter extends EntityConverter {

  /**
   * Constructs a new EntityConverter.
   *
   * Pverride parent because no params are needed
   */
  public function __construct() {}

  /**
   * {@inheritdoc}
   */
  public function convert($value, $definition, $name, array $defaults) {
    // A $value of zero means that this is the are-you-sure page before the
    // transaction has been saved. The transaction is retrieved therefore not in
    // the normal way from the database but from the tempstore.
    if ($value) {
      return Transaction::loadBySerial($value);
    }
    return \Drupal::service('user.private_tempstore')
      ->get('TransactionForm')
      ->get('mcapi_transaction');
  }

  /**
   * {@inheritdoc}
   */
  public function applies($definition, $name, Route $route) {
    return $name == 'mcapi_transaction' && !empty($definition['serial']);
  }

}
