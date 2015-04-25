<?php

/**
 * @file
 * Contains \Drupal\mcapi\Controller\TransactionController.
 */

namespace Drupal\mcapi\Controller;

use Drupal\Component\Utility\SafeMarkup;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityInterface;
use Drupal\mcapi\TransactionInterface;

/**
 * Returns responses for Transaction routes.
 */
class TransactionController extends ControllerBase {

  /**
   * @param Drupal\mcapi\TransactionInterface $transaction
   *
   * @return array
   *  a render array
   */
  public function page(TransactionInterface $mcapi_transaction) {
    return $this->buildPage($mcapi_transaction);
  }

  /**
   * The _title_callback for the transaction.view route.
   *
   * @param Drupal\mcapi\TransactionInterface $transaction
   *
   * @return string
   *   The page title.
   */
  public function pageTitle(TransactionInterface $mcapi_transaction) {
    return SafeMarkup::checkPlain($mcapi_transaction->label());
  }

  /**
   * Builds a transaction page render array.
   *
   * @param Drupal\mcapi\TransactionInterface $transaction
   *
   * @return array
   *   a render array
   */
  protected function buildPage(TransactionInterface $transaction) {
    //we look to the 'view' operation to get the display settings.
    return array(
      'transaction' => $this->entityManager()
        ->getViewBuilder('mcapi_transaction')
        ->view($transaction)
    );
  }
}
