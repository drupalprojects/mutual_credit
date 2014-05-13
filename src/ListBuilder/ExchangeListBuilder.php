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
    $header['transactions'] = t('Transactions');
    $header['admin'] = t('Administrator');
    return $header + parent::buildHeader();
  }

  /**
   * Overrides Drupal\Core\Entity\EntityListBuilder::buildRow().
   */
  public function buildRow(EntityInterface $entity) {
    // @todo I don't understand this
    //Strict warning: Non-static method Drupal\Core\Utility\LinkGenerator::generate() should not be called statically, assuming $this from incompatible context in ExchangeListBuilder->buildRow()
    /*
    $row['title'] = LinkGenerator::generate(
      $entity->label(),
      'mcapi.exchange.view',
      array('mcapi_exchange' => $entity->id())
    );*/

    $row['title'] = l($entity->label(), 'exchange/'.$entity->id());
    $row['access'] = $entity->get('open')->value ? t('Open') : t('Closed');

    //this includes deleted transactions
    $row['members'] = $entity->members();
    //this includes deleted transactions
    $row['transactions'] = $entity->transactions();

    $row['administrator']['data'] = array(
      '#theme' => 'username',
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
    //TODO get the links in the new way
    $url = $entity->url();

    // Ensure the edit operation exists since it is access controlled.
    if (isset($operations['edit'])) {
      $operations['edit']['query'] = drupal_get_destination();
    }

    if ($this->storage->deactivatable($entity)) {
      $operations['deactivate'] = array(
        'title' => t('Deactivate'),
        'href' => $url . '/deactivate',
        'options' => array(),
        'weight' => 40,
      );
    }
    elseif (!$entity->get('open')->value) {
      $operations['activate'] = array(
        'title' => t('Activate'),
        'href' => $url . '/activate',
        'options' => array(),
        'weight' => -10,
      );
    }
    if (!$this->storage->deletable($entity)) {
      unset($operations['delete']);
    }

    return $operations;
  }
}
