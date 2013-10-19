<?php

/**
 * Definition of Drupal\mcapi\CurrencyListController.
 */

namespace Drupal\mcapi;

use Drupal\Core\Config\Entity\DraggableListController;
use Drupal\Core\Entity\EntityInterface;

/**
 * Provides a listing of contact categories.
 */
class CurrencyListController extends DraggableListController {
  /**
   * {@inheritdoc}
   */
  public function getFormID() {
    return 'currencies_list';
  }
  /**
   * Overrides Drupal\Core\Entity\EntityListController::buildHeader().
   */
  public function buildHeader() {
    $header['title'] = t('Title');
    $header['insurance'] = t('Issuance');
    $header['transactions'] = t('Transactions');
    return $header + parent::buildHeader();
  }

  /**
   * Overrides Drupal\Core\Entity\EntityListController::buildRow().
   */
  public function buildRow(EntityInterface $entity) {
    $row['title'] = array(
      '#markup' => $this->getLabel($entity),
    );
    $type_names = array(
      CURRENCY_TYPE_ACKNOWLEDGEMENT => t('Acknowledgement'),
      CURRENCY_TYPE_EXCHANGE => t('Exchange'),
      CURRENCY_TYPE_COMMODITY => t('Commodity')
    );
    $type = $entity->issuance ? $entity->issuance : CURRENCY_TYPE_ACKNOWLEDGEMENT;
    $row['insurance'] = array(
      '#markup' => $type_names[$type],
    );
    $row['transactions'] = array(
      '#markup' => 'FIXME',  // count(transaction_filter(array('currcode' => $entity->id()))),
    );
    return $row + parent::buildRow($entity);
  }
}