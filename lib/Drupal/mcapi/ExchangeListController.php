<?php

/**
 * Definition of Drupal\mcapi\ExchangeListController.
 */

namespace Drupal\mcapi;

use Drupal\Core\Entity\EntityListController;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Utility\LinkGenerator;

/**
 * Provides a listing of currencies
 */
class ExchangeListController extends EntityListController {

  /**
   * Overrides Drupal\Core\Entity\EntityListController::buildHeader().
   */
  public function buildHeader() {
    $header['name'] = t('Name');
    $header['access'] = t('Access');
    $header['members'] = t('Members');
    $header['transactions'] = t('Transactions');
    $header['admin'] = t('Administrator');
    return $header + parent::buildHeader();
  }

  /**
   * Overrides Drupal\Core\Entity\EntityListController::buildRow().
   */
  public function buildRow(EntityInterface $entity) {
    // @todo I don't understand this
    //Strict warning: Non-static method Drupal\Core\Utility\LinkGenerator::generate() should not be called statically, assuming $this from incompatible context in ExchangeListController->buildRow()
    /*
    $row['title'] = LinkGenerator::generate(
      $entity->label(),
      'mcapi.exchange.view',
      array('mcapi_exchange' => $entity->id())
    );*/

    $row['title'] = l($entity->label(), 'exchange/'.$entity->id());
    $row['access'] = $entity->get('open')-value ? t('Open') : t('Closed');

    //this includes deleted transactions
    $row['members'] = $entity->members();
    //this includes deleted transactions
    $row['transactions'] = $entity->transactions();

    $row['administrator']['data'] = array(
      '#theme' => username,
      '#account' => user_load($entity->get('uid')->value)
    );

    $row += parent::buildRow($entity);
    return $row;
  }

  /**
   * {@inheritdoc}
   */
  public function getOperations(EntityInterface $entity) {
    $operations = parent::getOperations($entity);
    $uri = $entity->uri();

    // Ensure the edit operation exists since it is access controlled.
    if (isset($operations['edit'])) {
      // For configuration entities edit path is the MENU_DEFAULT_LOCAL_TASK and
      // therefore should be accessed by the short route.
      $operations['edit']['href'] = $uri['path'].'/edit';
    }

    if ($entity->deactivatable($entity)) {
      $operations['deactivate'] = array(
        'title' => t('Deactivate'),
        'href' => $uri['path'] . '/deactivate',
        'options' => $uri['options'],
        'weight' => 40,
      );
    }
    elseif (!$entity->get('open')->value) {
      $operations['activate'] = array(
        'title' => t('Activate'),
        'href' => $uri['path'] . '/activate',
        'options' => $uri['options'],
        'weight' => -10,
      );
    }
    if (!$entity->deletable($entity)) {
      unset($operations['delete']);
    }

    return $operations;
  }
}
