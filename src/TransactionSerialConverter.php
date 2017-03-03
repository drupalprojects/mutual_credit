<?php

namespace Drupal\mcapi;

use Drupal\mcapi\Storage\TransactionStorage;
use Drupal\user\PrivateTempStoreFactory;
use Drupal\Core\ParamConverter\EntityConverter;
use Symfony\Component\Routing\Route;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

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

  protected $tempStore;

  /**
   * Constructs a new EntityConverter.
   *
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_type_manager
   *   The entity manager.
   */
  public function __construct(PrivateTempStoreFactory $user_tempstore) {
    $this->tempStore = $user_tempstore;
  }

  /**
   * {@inheritdoc}
   */
  public function convert($value, $definition, $name, array $defaults) {
    // A $value of zero means that this is the are-you-sure page before the
    // transaction has been saved. The transaction is retrieved therefore not in
    // the normal way from the database but from the tempstore.
    if ($value) {
      if ($transaction = TransactionStorage::loadBySerial($value, FALSE)) {
        return $transaction;
      }
      throw new NotFoundHttpException();
    }
    return $this->tempStore
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
