<?php

/**
 * Definition of Drupal\mcapi_exchanges.
 */

namespace Drupal\mcapi_exchanges;

use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Utility\LinkGenerator;
use Drupal\Core\Template\Attribute;

/**
 * Provides a listing of exchanges
 */
class ExchangeListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header = array(
      'name' => t('Name'),
      'active' => t('Active'),
      'open' => t('Privacy'),
      'members' => t('Members'),
      'transactions' => t('Transactions'),
    );
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    $row = array(
      'class' => array($entity->status->value ? 'enabled' : 'disabled'),
      'title' => array($entity->label()),
      'data' => array(
        'title' => $entity->toLink(),
        'active' => $entity->get('status')->value ? t('Active') : t('Deactivated'),
        'open' => $entity->get('open')->value ? t('Open') : t('Private'),
        'members' => count($entity->memberIds('user')),
        'transactions' => count($entity->memberIds('mcapi_transaction'))
      )
    );
    $row['data'] += parent::buildRow($entity);
    return $row;
  }

  /**
   * {@inheritdoc}
   */
  public function getOperations(EntityInterface $entity) {
    $operations = parent::getOperations($entity);
    // Ensure the edit operation exists since it is access controlled.
    if (isset($operations['edit'])) {
      $operations['edit']['query'] = drupal_get_destination();
    }

    if ($entity->deactivatable()) {
      $operations['disable'] = [
        'title' => t('Deactivate'),
        'weight' => 40,
        'url' => $entity->toUrl('disable-confirm')
      ];
    }
    elseif (!$entity->get('status')->value) {
      $operations['enable'] = [
        'title' => t('Activate'),
        'weight' => -10,
        'url' => $entity->toUrl('enable-confirm')
      ];
      $operations['delete'] = [
        'title' => t('Delete'),
        'weight' => -10,
        'url' => $entity->toUrl('delete-confirm')
      ];
    }
    return $operations;
  }

  /**
   * {@inheritdoc}
   */
  public function load() {
    $entities = array(
      'enabled' => [],
      'disabled' => [],
    );
    foreach (parent::load() as $entity) {
      if ($entity->status->value) {
        $entities['enabled'][] = $entity;
      }
      else {
        $entities['disabled'][] = $entity;
      }
    }
    return $entities;
  }

  /**
   * {@inheritdoc}
   */
  public function render() {
    $entities = $this->load();
    $list['table'] = array(
      '#type' => 'table',
      '#attributes' => ['class' => ['exchanges-listing-table']],
      '#header' => $this->buildHeader(),
      '#rows' => [],
    );
    //order the rows putting enabled exchanges first
    foreach (array('enabled', 'disabled') as $status) {
      foreach ($entities[$status] as $entity) {
        $list['table']['#rows'][$entity->id()] = $this->buildRow($entity);
      }
    }
    return $list;
  }
}
