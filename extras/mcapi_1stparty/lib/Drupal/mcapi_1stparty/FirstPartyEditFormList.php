<?php

/**
 * Definition of Drupal\firstparty_forms\FirstPartyEditFormList.
 */

namespace Drupal\firstparty_forms;

use Drupal\Core\Config\Entity\ConfigEntityListController;
use Drupal\Core\Entity\EntityInterface;

/**
 * Provides a listing of contact categories.
 */
class FirstPartyEditFormList extends ConfigEntityListController {

  /**
   * Overrides Drupal\Core\Entity\EntityListController::buildHeader().
   */
  public function buildHeader() {
    $header['title'] = t('Title');
    $header['currencies'] = t('Restricted to currencies');
    $header['transaction_type'] = t('Starts workflow');
    return $header + parent::buildHeader();
  }

  /**
   * Overrides Drupal\Core\Entity\EntityListController::buildRow().
   */
  public function buildRow(EntityInterface $entity) {
  	$style = array('style' => $entity->status ? '' : 'color:#999');
  	//$class = array('style' => $entity->status ? 'enabled' : 'disabled');

    $row['title'] = $style + array(
    	'data' => array('#markup' => $entity->id())
    );

    //a list of the currencies used in this form
    $row['currencies'] = $style + array(
    	'data' => array('#markup' => 'currencies')
    );

    $row['transaction_type'] = $style + array(
    	'data' => array('#markup' => $entity->type)
    );

    //TODO load mcapi.css somehow to show the disabled forms in gray using ths class above.
    //Also see the McapiForm list controller.
    return $row + parent::buildRow($entity);
  }

}