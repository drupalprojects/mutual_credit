<?php

/**
 * Definition of Drupal\mcapi\ListBuilder\CurrencyListBuilder.
 * @todo inject current_user and entity_type.manager
 */

namespace Drupal\mcapi\ListBuilder;

use Drupal\Core\Config\Entity\DraggableListBuilder;
use Drupal\Core\Entity\EntityInterface;
use Drupal\mcapi\Entity\Currency;
use Drupal\Core\Render\Element;

/**
 * Provides a listing of currencies
 */
class CurrencyListBuilder extends DraggableListBuilder {

  /**
   * {@inheritdoc}
   */
  public function getFormID() {
    return 'currencies_list';
  }

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['title'] = t('Title');
    $header['transactions'] = t('Uses');
    $header['volume'] = t('Volume');
    $header['issuance'] = t('Issuance');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   *
   */
  public function buildRow(EntityInterface $entity) {
    $actions = parent::buildRow($entity);
    if (empty($actions)) {
      return;
    }
    $row['title'] = ['#markup' => $entity->link(NULL, 'canonical')];

    $type = $entity->issuance ? $entity->issuance : Currency::TYPE_ACKNOWLEDGEMENT;

    $count = $entity->transactionCount();
    //this includes deleted transactions
    $row['transactions'] = [
      '#markup' => $count
    ];

    //this includes deleted transactions
    $row['volume'] = [
      '#markup' => $entity->format($entity->volume(['state' => NULL]))
    ];
    $row['issuance'] = [
      '#markup' => Currency::issuances()[$type],
    ];
    //make sure that a currency with transactions in the database can't be deleted.
    if ($count) {
      unset($actions['operations']['data']['#links']['delete']);
    }
    return $row + $actions;
  }

  /*
   * remove the delete link if there is only one currency
   */
  public function render() {
    $build = parent::render();
    $children = Element::children($build['entities']);
    if (count($children) == 1) {
      $id = reset($children);
      unset($build['entities'][$id]['operations']['data']['#links']['delete']);
    }
    return $build;

  }

  /**
   * {@inheritdoc}
   */
  public function load() {
    //just show the currencies the current user has permission to edit
    //get the currencies in all exchanges of which current user is manager
    //if (\Drupal::currentUser()->hasPermission('manage mcapi')) {
      $curr_ids = $this->getStorage()->getQuery()
        ->sort('name')
        ->execute();
    //}
    /*
    else {
      $exchange_ids = \Drupal::entityTypeManager()
        ->getStorage('mcapi_exchange')->getQuery()
        ->condition('manager', $currentUser->id())
        ->execute();
      $curr_ids = Exchanges::getCurrenciesOfExchanges($exchange_ids);
    }
     *
     */
    return $this->storage->loadMultiple($curr_ids);//no sort has been applied
  }

}
