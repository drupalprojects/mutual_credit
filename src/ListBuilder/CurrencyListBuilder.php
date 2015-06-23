<?php

/**
 * Definition of Drupal\mcapi\ListBuilder\CurrencyListBuilder.
 */

namespace Drupal\mcapi\ListBuilder;

use Drupal\Core\Config\Entity\DraggableListBuilder;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Render\Element;

/**
 * Provides a listing of currencies
 *
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
    $type_names = array(
      CURRENCY_TYPE_ACKNOWLEDGEMENT => t('Acknowledgement'),
      CURRENCY_TYPE_EXCHANGE => t('Exchange'),
      CURRENCY_TYPE_COMMODITY => t('Commodity')
    );
    $type = $entity->issuance ? $entity->issuance : CURRENCY_TYPE_ACKNOWLEDGEMENT;

    $count = $entity->transactions(array('curr_id' => $entity->id()));
    //this includes deleted transactions
    $row['transactions'] = array(
      '#markup' => $count
    );

    //this includes deleted transactions
    $row['volume'] = array(
      '#markup' => $entity->format($entity->volume(array('state' => NULL)))
    );
    $row['issuance'] = array(
      '#markup' => $type_names[$type],
    );
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
    $children = element::children($build['entities']);
    if (count($children) == 1) {
      $id = reset($children);
      unset($build['entities'][$id]['operations']['data']['#links']['delete']);
    }
    return $build;
  }


  /**
   * {@inheritdoc}
   *
   * @todo make the currency filter work when the views filter works
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

}
