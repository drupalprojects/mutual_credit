<?php

/**
 * @file
 * Contains \Drupal\mcapi\Controller\Workflow.
 */

namespace Drupal\mcapi\Controller;

//no idea about these
//use Drupal\Component\Utility\Xss;
use Drupal\Core\Controller\ControllerBase;
use Drupal\system\SystemManager;

/**
 * Displays the workflow page in the management menu admin/accounting/workflow
 */
class Workflow extends ControllerBase {//what is the ControllerBase for? are we using it?

  function summaryPage() {
  	$renderable = array();

    //TODO check this works when the menu system settles down
    $renderable[] = array(
    	'#theme' => 'admin_block_content',
    	'#content' => \Drupal::service('system.manager')->getAdminBlock(menu_get_item()),
    	'#attributes' => array('style' => 'clear:both')
    );


    //TODO ideally these ops should be in a draggable list
    //but I don't yhink it is worth the effort just for user 1 to change the display
    //order of the operations
    $renderable['ops'] = array(
      '#theme' => 'table',
      '#header' => array(t('Operation name'), t('Description')),
      '#attributes' => array('style' => 'clear:both;width:100%;padding-top:2em;'),
      '#rows' => array()
    );
    foreach (transaction_operations(NULL, TRUE) as $op => $plugin) {
      $renderable['ops']['#rows'][$op] = array(
        'name' => $plugin->label,
        'description' => $plugin->description
      );
      if ($op == 'view') continue;
      $renderable['ops']['#rows'][$op]['settings'] = $this->l(
        $this->t('Settings'),
        'mcapi.workflow_settings',
        array('op' => $op)
      );
    }

    return $renderable;
  }



}
