<?php

/**
 * @file
 * Definition of Drupal\mcapi_exchanges\CurrencyListBuilderExchanges.
 */

namespace Drupal\mcapi_exchanges\Overrides;

use Drupal\mcapi\ListBuilder\CurrencyListBuilder;
use Drupal\mcapi_exchanges\Entity\Exchange;
use Drupal\mcapi_exchanges\Exchanges;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a listing of currencies
 */
class CurrencyListBuilderExchanges extends CurrencyListBuilder {

  private $currentuser;
  private $exchangeStorage;
  private $database;

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    return new static(
      $entity_type,
      $container->get('entity_type.manager'),
      $container->get('current_user'),
      $container->get('database')
    );
  }

  /**
   * Constructs a new EntityListBuilder object.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type definition.
   * @param \Drupal\Core\Entity\EntityStorageInterface $storage
   *   The entity storage class.
   */
  public function __construct(EntityTypeInterface $entity_type, $entity_type_manager, $current_user, $database) {
    parent::__construct($entity_type, $entity_type_manager->getStorage('mcapi_currency'));
    $this->currentUser = $current_user;
    $this->exchangeStorage = $entity_type_manager->getStorage('mcapi_exchange');
    $this->database = $database;
  }

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['exchanges'] = t('Used in');
    return parent::buildHeader() + $header;
  }

  /**
   * {@inheritdoc}
   * @todo we might want to somehow filter the currencies before they get here, if there are large number
   */
  public function buildRow(EntityInterface $entity) {
    $used_in = db_select('mcapi_exchange__currencies', 'c')
      ->fields('c', array('entity_id'))
      ->condition('currencies_target_id', $entity->id())
      ->execute()->fetchCol();
    $used_in_exchange_ids = \Drupal::entityTypeManager()
      ->getStorage('mcapi_exchange')->getQuery()
      ->condition('currencies', $entity->id())
      ->count()
      ->execute();
    if (count($used_in) > 1) {
      $row['exchanges']['#markup'] = $this->t('@count exchanges', array('@count' => count($used_in)));
    }
    else {
      $names = [];
      foreach (Exchange::loadMultiple($used_in) as $e) {
        $names[] = $e->link();
      }
      $row['exchanges']['#markup'] = implode(', ', $names);

    }
    return parent::buildRow($entity) + $row;
  }


  /**
   * {@inheritdoc}
   */
  public function load() {
    //just show the currencies the current user has permission to edit
    //get the currencies in all exchanges of which current user is manager
    if ($this->currentUser->hasPermission('manage mcapi')) {
      return parent::load();
    }
    //@todo, change this to get the exchanges in which I have manager role
    $exchange_ids = $this->exchangeStorage->getQuery()
      ->condition('manager', $currentUser->id())
      ->execute();
    $curr_ids = Exchanges::getCurrenciesOfExchanges($exchange_ids);

    return $this->storage->loadMultiple($curr_ids);//no sort has been applied
  }

}

  /**
   * {@inheritdoc}
   *
   * @todo make the currency filter work like the views filter works
   * @note that we must choose between currency weights and the paged / filtered list
   * @see \Drupal\views_ui\ViewListBuilder::render
   */
  /*
  public function render() {
    $this->limit = 2;//set this to 50 after testing
    $list = parent::render();

    $list['filters'] = [
      '#type' => 'search',
      '#title' => $this->t('Filter'),
      '#title_display' => 'invisible',
      '#size' => 40,
      '#placeholder' => $this->t('Filter by currency name or machine name'),
      '#weight' => -1,
      '#attributes' => array(
        'class' => array('views-filter-text'),
        'data-table' => '.views-listing-table',
        'autocomplete' => 'off',
        'title' => $this->t('Enter a part of the view name or description to filter by.'),
      ),
    ];
    return $list;
  }
   *
   */