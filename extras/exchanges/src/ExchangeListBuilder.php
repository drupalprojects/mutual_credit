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
      'access' => t('Access'),
      'members' => t('Members'),
      'transactions' => t('Transactions'),
      'admin' => t('Administrator')
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
        'title' => $entity->link(),
        'access' => $entity->get('status')->value ? t('Open') : t('Closed'),
        'members' => count($entity->users()),
        'transactions' => $entity->transactions(),
        'administrator' => array(
           'data' => array(
            '#theme' => 'username',
             '#account' => $entity->get('uid')->entity
          )
        )
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
      $operations['disable'] = array(
        'title' => t('Deactivate'),
        'weight' => 40,
        //'href' => $url . '/disable'
      ) + $entity->urlInfo('disable')->toArray();
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

  /**
   * {@inheritdoc}
   */
  public function render() {
    $entities = $this->load();
    $list['table'] = array(
      '#type' => 'table',
      '#attributes' => new Attribute(
        array('class' => array('exchanges-listing-table'))
      ),
      '#header' => $this->buildHeader(),
      '#rows' => array(),
    );
    foreach (array('enabled', 'disabled') as $status) {
      foreach ($entities[$status] as $entity) {
        $list['table']['#rows'][$entity->id()] = $this->buildRow($entity);
      }
    }
    //TODO look in common.inc drupal_process_attached() to see how to add arbitrary bits of css
    debug('need to add arbitrary css');
//    _drupal_add_css('table.exchanges-listing-table tr.disabled{color:#999;}', array('type' => 'inline'));
    return $list;
  }
}
