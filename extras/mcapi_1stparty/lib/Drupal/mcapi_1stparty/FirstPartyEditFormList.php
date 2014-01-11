<?php

/**
 * Definition of Drupal\mcapi_1stparty\FirstPartyEditFormList.
 */

namespace Drupal\mcapi_1stparty;

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
    $header['currencies'] = t('Currencies');
    $header['transaction_type'] = t('Workflow');
    return $header + parent::buildHeader();
  }

  /**
   * Overrides Drupal\Core\Entity\EntityListController::buildRow().
   */
  public function buildRow(EntityInterface $entity) {

  	$style = array('style' => $entity->status ? '' : 'color:#999');
  	//$class = array('style' => $entity->status ? 'enabled' : 'disabled');

  	//TODO make a link out of this
    $row['title'] = $style + array(
    	'data' => array(
    	  '#type' => 'link',
    	  '#title' => $entity->label(),
    	  '#route_name' => 'mcapi.1stparty',
    	  '#route_parameters' => array(
    	    '1stparty_editform' => $entity->id()
    	  )
    	)
    );

    //a list of the currencies used in this form
    if (count($entity->worths)) {
      //echo count($entity->worths);
      print_r(array_keys($entity->worths));
      $currs = array();
      //still isn't working...
      foreach ($entity->worths[0] as $worth) {
        $currs[] = $worth->currency->label();
      }
    }
    $row['currencies'] = $style + array(
    	'data' => array(
    	  '#markup' => empty($currs) ? t('Any') : implode(', ', $currs)
    	)
    );

    $row['transaction_type'] = $style + array(
    	'data' => array('#markup' => $entity->type)
    );

    //TODO load mcapi.css somehow to show the disabled forms in gray using ths class above.
    //Also see the McapiForm list controller.
    return $row + parent::buildRow($entity);
  }

}
