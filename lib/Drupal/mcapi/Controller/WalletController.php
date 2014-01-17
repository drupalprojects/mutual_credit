<?php

/**
 * @file
 * Contains \Drupal\mcapi\Controller\WalletController.
 */

namespace Drupal\mcapi\Controller;

//no idea about these
//use Drupal\Component\Utility\Xss;
use Drupal\Core\Controller\ControllerBase;
use Drupal\system\SystemManager;

/**
 *
 */
class WalletController extends ControllerBase {//what is the ControllerBase for? are we using it?

  /**
   * List all the transactions
   * Actually this is just a view, with some careful access control
   * the router item and this Controller can be moved.
   */
  function page($entity) {
    $build = array('#markup' => 'transaction listing....')
  }

  /**
   *
   */
  function pageTitle($entity) {
    return t('History of !walletname', array('!walletname' => $entity->label()));
  }

}
