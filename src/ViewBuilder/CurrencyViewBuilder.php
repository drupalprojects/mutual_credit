<?php

/**
 * @file
 * Definition of Drupal\mcapi\ViewBuilder\CurrencyViewBuilder.
 *
 */

namespace Drupal\mcapi\ViewBuilder;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityViewBuilder;

/**
 * Visualisations of transactions per currency
 */
class CurrencyViewBuilder extends EntityViewBuilder {

  public function getBuildDefaults(EntityInterface $entity, $view_mode) {
    $renderable = [];

    $renderable['filter'] = [
      '#title' => $this->t('Filter'),
      '#type' => 'details',
      //@todo inject this
      'form' => \Drupal::formBuilder()->getForm('\Drupal\mcapi\Form\TransactionStatsFilterForm')
    ];

    //dsm(\Drupal::request()->query->all());
    //$conditions['until'] = REQUEST_TIME;

    $renderable['snapshot'] = [
      '#type' => 'mcapi_overview',
      '#curr_id' => $entity->id(),
      '#conditions' => [],//@todo
      '#weight' => 1,
    ];

    $renderable['#cache_contexts'] = ['accounting'];
    return $renderable;
  }

}

