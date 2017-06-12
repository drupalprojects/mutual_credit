<?php

namespace Drupal\mcapi\ListBuilder;

use Drupal\mcapi\Entity\Currency;
use Drupal\Core\Config\Entity\DraggableListBuilder;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Render\Element;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Entity\EntityTypeInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a listing of currencies.
 */
class CurrencyListBuilder extends DraggableListBuilder {

  /**
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  protected $entityTypeManager;

  /**
   * {@inheritdoc}
   */
  public function __construct($entity_type, $storage, EntityTypeManager $entity_type_manager) {
    parent::__construct($entity_type, $storage);
    $this->entityTypeManager = $entity_type_manager;
  }


  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    return new static(
      $entity_type,
      $container->get('entity_type.manager')->getStorage($entity_type->id()),
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'mcapi_currency_collection';
  }

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['title'] = t('Title');
    $header['volume'] = t('Volume');
    $header['trades'] = t('Trades');
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
    $stats = $this->entityTypeManager
      ->getStorage('mcapi_transaction')
      ->ledgerStateQuery($entity->id(), [])
      ->execute()
      ->fetch();
    // This includes deleted transactions.
    $row['volume'] = [
      '#markup' => $entity->format($stats->volume)->toString(),
    ];
    $row['trades'] = [
      '#markup' => $stats->trades,
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
    $curr_ids = $this->getStorage()->getQuery()
      ->sort('name')
      ->execute();
    // no sort has been applied.
    return $this->storage->loadMultiple($curr_ids);
  }

}
