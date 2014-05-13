<?php

/**
 * @file
 * Contains \Drupal\mcapi\Controller\Workflow.
 * @TODO this would like to be a draggable list,
 * but the DraggableListBuilder is designed for entities, not plugins
 */

namespace Drupal\mcapi\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\system\SystemManager;
use Symfony\Cmf\Component\Routing\RouteObjectInterface;

/**
 * Displays the workflow page in the management menu admin/accounting/workflow
 */
class Workflow extends ControllerBase {//what is the ControllerBase for? are we using it?

  function summaryPage() {
  	$renderable = array();

    //TODO ideally these ops should be in a draggable list
    //but I don't yhink it is worth the effort just for user 1 to change the display
    //order of the operations
    $renderable['ops'] = array(
      '#theme' => 'table',
      '#header' => array(t('Operation name'), t('Description')),
      '#attributes' => array('style' => 'clear:both;width:100%;padding-top:2em;'),
      '#rows' => array()
    );
    foreach (transaction_operations() as $op => $plugin) {
      $renderable['ops']['#rows'][$op] = array(
        'name' => $plugin->label,
        'description' => $plugin->description
      );
      $renderable['ops']['#rows'][$op]['settings'] = $this->l(
        $this->t('Settings'),
        'mcapi.workflow_settings',
        array('op' => $op)
      );
    }

    return $renderable;
  }



}
