<?php

/**
 * @file
 * Contains \Drupal\mcapi\ParamConverter\TransactionSerialConverter.
 */

namespace Drupal\mcapi\ParamConverter;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Route;
use Drupal\Core\ParamConverter\EntityConverter;
use Drupal\Core\ParamConverter\ParamConverterInterface;
use Drupal\mcapi\Entity\Transaction;

/**
 * Provides upcasting for a transaction entity to be used in the Views UI.
 *
 * Example:
 *
 * pattern: '/transaction/{transaction_serial}'
 * options:
 *   parameters:
 *     transaction_serial:
 *       type: 'entity:mcapi_transaction'
 *       serial: TRUE
 *
 * The value for {view} will be converted to a view entity prepared for the
 * Views UI and loaded from the views temp store, but it will not touch the
 * value for {bar}.
 * If transaction_serial is 0, the transaction will be pulled from the tempstore
 */
class TransactionSerialConverter extends EntityConverter implements ParamConverterInterface {

  /**
   * {@inheritdoc}
   */
  public function convert($value, $definition, $name, array $defaults, Request $request) {
    //a $value of zero means that this is the are-you-sure page before the transaction has been saved
    //the transaction is retrieved therefore not in the normal way from the database but from the tempstore
    if ($value) {
      return Transaction::loadBySerials($value);
    }
    return \Drupal::service('user.tempstore')->get('TransactionForm')->get('entity');
  }

  /**
   * {@inheritdoc}
   */
  public function applies($definition, $name, Route $route) {
    if (parent::applies($definition, $name, $route)) {
      return !empty($definition['serial']) && $definition['type'] === 'entity:mcapi_transaction';
    }
    return FALSE;
  }
}
