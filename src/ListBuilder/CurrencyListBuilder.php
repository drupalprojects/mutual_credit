<?php

namespace Drupal\mcapi\ListBuilder;

use Drupal\Core\Config\Entity\DraggableListBuilder;
use Drupal\Core\Entity\EntityInterface;
use Drupal\mcapi\Entity\Currency;
use Drupal\Core\Render\Element;

/**
 * Provides a listing of currencies.
 */
class CurrencyListBuilder extends DraggableListBuilder {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
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
   */
  public function buildRow(EntityInterface $entity) {
    $actions = parent::buildRow($entity);
    if (empty($actions)) {
      return;
    }
    $row['title'] = ['#markup' => $entity->toLink()->toString()];

    $stats = $entity->stats();
    debug($stats);
    // This includes deleted transactions.
    $row['transactions'] = [
      '#markup' => $stats->trades,
    ];

    // This includes deleted transactions.
    $row['volume'] = [
      '#markup' => $entity->format($stats->volume),
    ];
    $row['issuance'] = [
      '#markup' => Currency::issuances()[$entity->issuance],
    ];
    // A currency with transactions in the database must not be deleted.
    if ($stats->trades) {
      unset($actions['operations']['data']['#links']['delete']);
    }
    return $row + $actions;
  }

  /**
   * Remove the delete link if there is only one currency.
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
    // Just show the currencies the current user has permission to edit
    // get the currencies in all exchanges of which current user is manager
    // if (\Drupal::currentUser()->hasPermission('manage mcapi')) {.
    $curr_ids = $this->getStorage()->getQuery()
        ->sort('name')
        ->execute();
    // }
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
    // no sort has been applied.
    return $this->storage->loadMultiple($curr_ids);
  }

}
