<?php

/**
 * @file
 * Definition of Drupal\mcapi_exchanges\Overrides\FirstPartyEditFormListBuilder.
 */

namespace Drupal\mcapi_exchanges\Overrides;

use Drupal\Core\Entity\EntityInterface;
use Drupal\mcapi_exchanges\Entity\Exchange;//only if enabled!
use Drupal\mcapi_1stparty\FirstPartyEditFormListBuilder;

/**
 * Provides a listing of editable transaction forms
 */
class FirstPartyEditFormListBuilderExchanges extends FirstPartyEditFormListBuilder{

  /**
   * Overrides Drupal\Core\Entity\EntityListBuilder::buildHeader().
   * @Todo inherit this
   */
  public function buildHeader() {
    $header = ['exchange' => t('Exchange')];
    $header += parent::buildHeader();
    return $header + parent::buildHeader();
  }

  /**
   * Overrides Drupal\Core\Entity\EntityListBuilder::buildRow().
   * @Todo inherit this
   */
  public function buildRow(EntityInterface $entity) {
    $admin = $this->currentUser()->hasPermission('configure mcapi');
    $exchange = Exchange::load($entity->exchange);    
    $style = array('style' => $entity->status ? '' : 'color:#999');
    //only show forms in exchanges that the current user is a member of
    if ($admin || $exchange->hasMember()) {
      $row['exchange'] = $style + array(
        'data' => array(
          '#markup' => $exchange ? $exchange->label() : t('- All -')
        )
      );
      return $row + parent::buildRow($entity); 
    }
  }

}
