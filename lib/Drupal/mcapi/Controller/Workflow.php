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

  	drupal_set_message('States & types SHOULD go in a yml file', 'warning');
  	//TODO lay this page out more attractively
    $renderable['states'] = array(
    	'#theme' => 'table',
    	'#attributes' => array('class' => array('help')),
    	'#caption' => t('States'),
      '#header' => array(t('Name'), t('Description')),
      '#rows' => array()
    );
    foreach(mcapi_get_states('#full') as $constant => $info) {
    	$renderable['states']['#rows'][$constant] = $info;
    }

    $renderable['types'] = array(
    	'#theme' => 'table',
    	'#attributes' => array('class' => array('help')),
    	'#caption' => t('Types'),
      '#header' => array(t('Name')),
      '#rows' => array()
    );
    foreach(mcapi_get_types() as $type) {
    	//these have no real metadata for now
    	$renderable['types']['#rows'][$type] = array($type);
    }
    //TODO Tidy up the preceding tables
    //I can't see how to inject a bit of css into the top of the page since drupal_add_css is deprecated
    $renderable['#prefix'] = "<style>table.help{margin-bottom:2em;}.help td{background-color:#efefef;}</style>";

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
    foreach (transaction_operations('1', '0') as $op => $plugin) {
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
