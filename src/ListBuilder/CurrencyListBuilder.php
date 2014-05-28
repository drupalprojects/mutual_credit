<?php

/**
 * Definition of Drupal\mcapi\ListBuilder\CurrencyListBuilder.
 */

namespace Drupal\mcapi\ListBuilder;

use Drupal\Core\Config\Entity\DraggableListBuilder;
use Drupal\Core\Entity\EntityInterface;

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
   * Overrides Drupal\Core\Entity\EntityListBuilder::buildHeader().
   */
  public function buildHeader() {
    $header['title'] = t('Title');
    $header['transactions'] = t('Uses');
    $header['volume'] = t('Volume');
    $header['issuance'] = t('Issuance');
    $header['exchanges'] = t('Used in');
    return $header + parent::buildHeader();
  }

  /**
   * Overrides Drupal\Core\Entity\EntityListBuilder::buildRow().
   * @todo we might want to somehow filter the currencies before they get here, if there are large number
   */
  public function buildRow(EntityInterface $entity) {
    $actions = parent::buildRow($entity);
    if (empty($actions)) continue;

    $row['title'] = array(
      '#markup' => $this->getLabel($entity),
    );
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
    $names = array();
    foreach (entity_load_multiple('mcapi_exchange', $entity->used_in()) as $e) {
      $names[] = l($e->label(), $e->url());
    }
    $row['exchanges'] = array(
    	'#markup' => implode(', ', $names)
    );

    //make sure that a currency with transactions in the database can't be deleted.
    if ($count) {
      unset($actions['operations']['data']['#links']['delete']);
    }

    return $row + $actions;
  }


  /**
   * {@inheritdoc}
	 * ensure that the last currency can't be switched off or disabled
   */
  public function getOperations(EntityInterface $entity) {
  	$operations = parent::getOperations($entity);

    //you can only delete disabled currencies (and only then if 'indelible accounting' setting is disabled)
  	if (!$this->storage->deletable($entity)) {
  	  unset($operations['delete']);
  	}

  	return $operations;
  }

}
