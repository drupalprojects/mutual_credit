<?php

/**
 * @file
 * Contains \Drupal\mcapi\ParamConverter\TransactionSerialConverter.
 */

namespace Drupal\mcapi\ParamConverter;

use Drupal\Core\Entity\EntityManager;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Route;
use Drupal\Core\ParamConverter\EntityConverter;
use Drupal\Core\ParamConverter\ParamConverterInterface;
use Drupal\mcapi\McapiTransactionException;

/**
 * Provides upcasting for a view entity to be used in the Views UI.
 *
 * Example:
 *
 * pattern: '/some/{transaction_serial}'
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
    if ($value == '0') {
      //transaction hasn't been created yet,
      //we're loading the create operation, and the transaction from the tempstore
      $entity = \Drupal::service('user.tempstore')->get('TransactionForm')->get('entity');
      return $entity;
    }
    else {
      $entity = $this->entityManager
        ->getStorageController('mcapi_transaction')
        ->loadByProperties(array('serial' => $value));
      if (!empty($entity)) {
        return reset($entity);
      }
    }
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
