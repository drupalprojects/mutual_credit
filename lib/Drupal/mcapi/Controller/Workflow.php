<?php

/**
 * @file
 * Contains \Drupal\mcapi\Controller\Workflow.
 */

namespace Drupal\mcapi\Controller;

//no idea about these
use Drupal\Component\Utility\Xss;
use Drupal\Core\Controller\ControllerBase;

/**
 * Controller routines for user routes.
 */
class Workflow extends ControllerBase {

  function summaryPage() {
    drupal_set_message('should states, types and operations go in a yml file?', 'warning');
    $renderable = array(
      'ops' => array(
        '#theme' => 'table',
        '#caption' => t('Operations, from !hook.', array('!hook' => "hook_transaction_operations()")),
        '#header' => array(t('Name'), t('Description')),
      ),
    );
    foreach (transaction_operations('1', '0') as $op => $info) {
      if ($op == 'mcapi_view')continue;
      $renderable['ops']['#rows'][$op] = array(
        'name' => $info['title'],
        'description' =>  array_key_exists('description', $info) ? $info['description'] : '',
        'settings' => $info['mail'] ? l(t('Settings'), 'admin/accounting/workflow/op/'.$op) : ''
      );
    }

    debug(
    '\Drupal\system\Controller\SystemController::systemAdminMenuBlockPage',
    'How do I call up this page controller iwitin this page?'
    );

    $renderable['items']['#markup'] = '';
    return $renderable;
  }


}
