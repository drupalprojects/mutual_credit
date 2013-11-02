<?php

/**
 * @file
 * Definition of Drupal\mcapi\CurrencyStorageController.
 */

namespace Drupal\mcapi;

use Drupal\Core\Config\Entity\ConfigStorageController;

class CurrencyStorageController extends ConfigStorageController {
  /**
   * {@inheritdoc}
   */
  protected function attachLoad(&$queried_entities, $revision_id = FALSE) {
    parent::attachLoad($queried_entities, $revision_id);

    foreach ($queried_entities as $entity) {
      if ($entity->display['divisions'] == CURRENCY_DIVISION_MODE_CUSTOM) {
        foreach(explode("\n", $entity->display['divisions_setting']) as $line) {
          list($cent, $display) = explode('|', $line);
          $entity->display['divisions_allowed'][$cent] = trim($display);
        }
      }
    }
  }
}