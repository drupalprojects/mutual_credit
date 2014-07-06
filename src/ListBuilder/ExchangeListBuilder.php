<?php

/**
 * Definition of Drupal\mcapi\ListBuilder\ExchangeListBuilder.
 */

namespace Drupal\mcapi\ListBuilder;

use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Utility\LinkGenerator;

/**
 * Provides a listing of exchanges
 */
class ExchangeListBuilder extends EntityListBuilder {

  /**
   * Overrides Drupal\Core\Entity\EntityListBuilder::buildHeader().
   */
  public function buildHeader() {
    $header['name'] = t('Name');
    $header['access'] = t('Access');
    $header['members'] = t('Members');
    $header['admin'] = t('Administrator');
    return $header + parent::buildHeader();
  }

  /**
   * Overrides Drupal\Core\Entity\EntityListBuilder::buildRow().
   */
  public function buildRow(EntityInterface $entity) {
    $row['class'][] = $entity->status->value ? 'enabled' : 'disabled';
    $row['title'][] = $entity->label();

    $row['data']['title'] = l($entity->label(), 'exchange/'.$entity->id());
    $row['data']['access'] = $entity->get('status')->value ? t('Open') : t('Closed');
    $row['data']['members'] = $entity->users();
    $row['data']['administrator']['data'] = array(
      '#theme' => 'username',
      '#account' => user_load($entity->get('uid')->value)
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

    if ($this->storage->deactivatable($entity)) {
      $operations['disable'] = array(
        'title' => t('Deactivate'),
        'weight' => 40,
        //'href' => $url . '/disable'
      ) + $entity->urlInfo('enable')->toArray();
    }
    elseif (!$entity->get('status')->value) {
      $operations['enable'] = array(
        'title' => t('Activate'),
        'weight' => -10,
        //'href' => $url . '/enable'
      ) + $entity->urlInfo('enable')->toArray();
    }
    return $operations;
  }

  /**
   * {@inheritdoc}
   */
  public function load() {
    $entities = array(
      'enabled' => array(),
      'disabled' => array(),
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

  public function render() {
    $entities = $this->load();
    $list['table'] = array(
      '#type' => 'table',
      '#attributes' => array(
        'class' => array('exchanges-listing-table'),
      ),
      '#header' => $this->buildHeader(),
      '#rows' => array(),
    );
    foreach (array('enabled', 'disabled') as $status) {
      foreach ($entities[$status] as $entity) {
        $list['table']['#rows'][$entity->id()] = $this->buildRow($entity);
      }
    }
    _drupal_add_css('table.exchanges-listing-table tr.disabled{color:#999;}', array('type' => 'inline'));
    return $list;
  }
}
