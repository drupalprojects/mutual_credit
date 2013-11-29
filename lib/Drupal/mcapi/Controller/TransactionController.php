<?php

/**
 * @file
 * Contains \Drupal\mcapi\Controller\TransactionController.
 */

namespace Drupal\mcapi\Controller;

use Drupal\Component\Utility\String;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityInterface;
use Drupal\mcapi\TransactionInterface;

/**
 * Returns responses for Transaction routes.
 */
class TransactionController extends ControllerBase {

  /**
   * @param Drupal\mcapi\TransactionInterface $transaction
   *  The Transaction that we are displaying.
   *
   * @return array
   *  An array suitable for drupal_render().
   */
  public function page(TransactionInterface $mcapi_transaction) {
    return $this->buildPage($mcapi_transaction);
  }

  /**
   * The _title_callback for the node.view route.
   *
   * @param NodeInterface $transaction
   *   The current transaction.
   *
   * @return string
   *   The page title.
   */
  public function pageTitle(TransactionInterface $mcapi_transaction) {
    return String::checkPlain($mcapi_transaction->label());
  }

  /**
   * Builds a transaction page render array.
   *
   * @param \Drupal\mcapi\TransactionInterface $transaction
   *   The transaction we are displaying.
   *
   * @return array
   *   An array suitable for drupal_render().
   */
  protected function buildPage(TransactionInterface $transaction) {
    return array(
      'transaction' => $this->entityManager()->getViewBuilder('mcapi_transaction')->view($transaction, 'certificate')
    );
  }
}
