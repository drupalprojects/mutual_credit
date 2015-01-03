<?php

/**
 * Definition of Drupal\mcapi_exchanges\CurrencyListBuilderExchanges.
 */

namespace Drupal\mcapi_exchanges;

use Drupal\Core\Entity\EntityInterface;
use Drupal\mcapi\ListBuilder\CurrencyListBuilder;

/**
 * Provides a listing of currencies
 */
class CurrencyListBuilderExchanges extends CurrencyListBuilder {

  /**
   * Overrides Drupal\Core\Entity\EntityListBuilder::buildHeader().
   */
  public function buildHeader() {
    $header['exchanges'] = t('Used in');
    return $header + parent::buildHeader();
  }

  /**
   * Overrides Drupal\Core\Entity\EntityListBuilder::buildRow().
   * @todo we might want to somehow filter the currencies before they get here, if there are large number
   */
  public function buildRow(EntityInterface $entity) {
    $used_in = $entity->used_in();
    if (count($used_in) > 1) {
      $row['exchanges']['#markup'] = $this->t('@count exchanges', array('@count' => count($used_in)));
    }
    else {
      foreach (Exchange::loadMultiple($used_in) as $e) {
        $names[] = $e->link();
      }
      $row['exchanges']['#markup'] = implode(', ', $names);
    }
      
    return $row + parent::buildRow($entity);
  }

}
