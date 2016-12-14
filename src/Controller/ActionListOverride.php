<?php

namespace Drupal\mcapi\Controller;

use Drupal\mcapi\TransactionOperations;
use Drupal\mcapi\Mcapi;
use Drupal\action\ActionListBuilder;
use Drupal\Component\Utility\Crypt;

/**
 * Extends the actionListBuilder to exclude the special transaction actions
 *
 * @see \Drupal\system\Entity\Action
 * @see action_entity_info()
 */
class ActionListOverride extends ActionListBuilder {

  /**
   * {@inheritdoc}
   */
  public function load() {
    $entities = parent::load();
    // Remove the mcapi actions from the list of actions.
    foreach (array_keys(TransactionOperations::loadAllActions()) as $id) {
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
    // Remove any transaction actions are already in use from the dropdown.
    foreach (TransactionOperations::loadAllActions() as $action) {
      $key = Crypt::hashBase64($action->getPlugin()->getPluginId());
      unset($options[$key]);
    }
    return $build;
  }

}
