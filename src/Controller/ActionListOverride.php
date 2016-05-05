<?php

/**
 * @file
 * Contains Drupal\mcapi\Controller\ActionListOverride.
 */

namespace Drupal\mcapi\Controller;
use Drupal\mcapi\Mcapi;
use Drupal\Component\Utility\Crypt;

/**
 * Defines a class to build a listing of action entities.
 *
 * @see \Drupal\system\Entity\Action
 * @see action_entity_info()
 */
class ActionListOverride extends \Drupal\action\ActionListBuilder {

  /**
   * {@inheritdoc}
   */
  public function load() {
    $entities = parent::load();
    //remove the mcapi actions from the list of actions
    foreach (array_keys(Mcapi::transactionActionsLoad()) as $id) {
      unset($entities[$id]);
    }
    return $entities;
  }

  /**
   * {@inheritdoc}
   */
  public function render() {
    $build = parent::render();
    $options = &$build['action_admin_manage_form']['parent']['action']['#options'];
    //remove any transaction actions from the dropdown which are already in use
    foreach (Mcapi::transactionActionsLoad() as $action) {
      $key = Crypt::hashBase64($action->getPlugin()->getPluginId());
        unset($options[$key]);
    }
    return $build;
  }

}
