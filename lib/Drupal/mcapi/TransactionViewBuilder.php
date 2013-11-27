<?php

/**
 * @file
 * Definition of Drupal\node\NodeViewBuilder.
 */

namespace Drupal\mcapi;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityViewBuilder;
use Drupal\entity\Entity\EntityDisplay;

/**
 * Render controller for nodes.
 */
class TransactionViewBuilder extends EntityViewBuilder {

  /**
   * Overrides Drupal\Core\Entity\EntityRenderController::buildContent().
   */
  public function buildContent(array $entities, array $displays, $view_mode, $langcode = NULL) {
    parent::buildContent($entities, $displays, $view_mode, $langcode);

    foreach ($entities as $entity) {
      $entity->content['created'] = array(
        '#markup' => format_date($entity->created->value, 'long'),
      );
      $entity->content['payer'] = array(
        '#markup' => $entity->payer->entity->name->value,
      );
      $entity->content['payee'] = array(
        '#markup' => $entity->payee->entity->name->value,
      );
      $entity->content['worths'] = array();

      foreach ($entity->worths[0] as $currency => $worth) {
        $entity->content['worths'][$currency] = array(
          '#prefix' => $worth->currency->prefix,
          '#suffix' => $worth->currency->suffix,
          '#markup' => $worth->quantity,
        );
      }
    }
  }
}