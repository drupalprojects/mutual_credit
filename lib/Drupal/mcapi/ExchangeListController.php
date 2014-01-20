<?php

/**
 * Definition of Drupal\mcapi\ExchangeListController.
 */

namespace Drupal\mcapi;

use Drupal\Core\Entity\EntityListController;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Utility\LinkGenerator;

/**
 * Provides a listing of currencies
 */
class ExchangeListController extends EntityListController {

  /**
   * Overrides Drupal\Core\Entity\EntityListController::buildHeader().
   */
  public function buildHeader() {
    $header['name'] = t('Name');
    $header['members'] = t('Members');
    $header['transactions'] = t('Transactions');
    return $header + parent::buildHeader();
  }

  /**
   * Overrides Drupal\Core\Entity\EntityListController::buildRow().
   */
  public function buildRow(EntityInterface $entity) {
    // @todo I don't understand this
    //Strict warning: Non-static method Drupal\Core\Utility\LinkGenerator::generate() should not be called statically, assuming $this from incompatible context in ExchangeListController->buildRow()
    /*
    $row['title'] = LinkGenerator::generate(
      $entity->label(),
      'mcapi.exchange.view',
      array('mcapi_exchange' => $entity->id())
    );*/

    $row['title'] = l($entity->label(), 'exchange/'.$entity->id());

    //this includes deleted transactions
    $row['members'] = $entity->members();
    //this includes deleted transactions
    $row['transactions'] = $entity->transactions();

    $row += parent::buildRow($entity);
    return $row;
  }

  /**
   * Remove the link for deleting the default exchange
   */
  public function getOperations(EntityInterface $entity) {
    $operations = parent::getOperations($entity);
    if ($entity->id() == 1) {
      unset($operations['delete']);
    }
    return $operations;
  }
}
