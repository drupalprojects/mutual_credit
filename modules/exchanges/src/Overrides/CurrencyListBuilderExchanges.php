<?php

namespace Drupal\mcapi_exchanges\Overrides;

use Drupal\mcapi\ListBuilder\CurrencyListBuilder;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\mcapi_exchanges\Exchanges;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a listing of currencies filtered by exchange
 */
class CurrencyListBuilderExchanges extends CurrencyListBuilder {

  private $currentuser;
  private $database;

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    return new static(
      $entity_type,
      $container->get('entity_type.manager')->getStorage('mcapi_currency'),
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
  public function __construct(EntityTypeInterface $entity_type, EntityStorageInterface $storage, $current_user, $database) {
    parent::__construct($entity_type, $storage);
    $this->currentUser = $current_user;
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
   *
   * @todo we might want to somehow filter the currencies before they get here, if there are large number
   */
  public function buildRow(EntityInterface $entity) {
    // Get the groups which use this currency
    $group_ids =\Drupal::entityQuery('group')
      ->condition('currencies', $entity->id)
      ->execute();

    if (count($group_ids) > 1) {
      $row['exchanges']['#markup'] = $this->t('@count exchanges', array('@count' => count($group_ids)));
    }
    else {
      $gid = reset($group_ids);
      $row['exchanges']['#markup'] = \Drupal\group\Entity\Group::load($gid)->toLink()->toString();
    }
    return parent::buildRow($entity) + $row;
  }

  /**
   * {@inheritdoc}
   */
  public function load() {
    // Just show the currencies the current user has permission to edit
    // get the currencies in all exchanges of which current user is manager.
    if ($this->currentUser->hasPermission('manage mcapi')) {
      return parent::load();
    }
    $gids = $currencies = [];
    //get my memberships
    $memberships = \Drupal\group\GroupMembership::loadByUser($this->currentUser, ['admin']);
    //get the groups from the memberships
    foreach ($memberships as $ship) {
      $gids[] = $ship->getGroup()->id();
    }
    if ($gids) {
      // Get the currencies in those groups
      $curr_ids = Exchanges::getCurrenciesOfExchanges($gids);
      // No sort has been applied.
      $currencies = $this->storage->loadMultiple($curr_ids);
    }
    return $currencies;
  }

}

/**
   * {@inheritdoc}
   *
   * @todo make the currency filter work like the views filter works
   *
   * @note that we must choose between currency weights and the paged / filtered list
   *
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
