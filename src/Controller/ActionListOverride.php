<?php

/**
 * @file
 * Contains Drupal\mcapi\Controller\ActionListOverride.
 */

namespace Drupal\mcapi\Controller;
use Drupal\mcapi\Mcapi;

/**
 * Defines a class to build a listing of action entities.
 *
 * @see \Drupal\system\Entity\Action
 * @see action_entity_info()
 */
class ActionListOverride extends \Drupal\action\ActionListBuilder {

  private $transactionActions;
  
  function __construct(EntityTypeInterface $entity_type, EntityStorageInterface $storage) {
    parent::__construct($entity_type, $storage);
    $this->transactionActions = Mcapi::transactionActionsLoad();
  }
    
  /**
   * {@inheritdoc}
   */
  public function load() {
    $entities = parent::load();
    //remove the mcapi actions
    foreach (array_keys($this->transactionActions) as $id) {echo $id;
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
    $key = array_search(t('Delete a transaction...'), $options);
    unset($options[$key]);
    return $build;
  }

}
