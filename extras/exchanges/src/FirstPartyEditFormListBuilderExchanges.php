<?php

/**
 * Definition of Drupal\mcapi_exchanges\FirstPartyEditFormListBuilder.
 */

namespace Drupal\mcapi_exchanges;

use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Form\FormInterface;
use Drupal\Core\Url;
use Drupal\mcapi_exchanges\Entity\Exchange;//only if enabled!
use Drupal\mcapi_1stparty\Entity\FirstPartyFormDesign;
use Drupal\mcapi_1stparty\FirstPartyEditFormListBuilder;

/**
 * Provides a listing of contact categories.
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
    $admin = \Drupal::currentUser()->hasPermission('configure mcapi');
    $exchange = Exchange::load($entity->exchange);    
    $style = array('style' => $entity->status ? '' : 'color:#999');
    //only show forms in exchanges that the current user is a member of
    if ($admin || $exchange->isMember()) {
      $row['exchange'] = $style + array(
        'data' => array(
          '#markup' => $exchange ? $exchange->label() : t('- All -')
        )
      );
      return $row + parent::buildRow($entity); 
    }
  }

}
