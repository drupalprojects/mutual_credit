<?php

/**
 * @file
 * Contains \Drupal\views_ui\ParamConverter\TransactionSerialConverter.
 */

namespace Drupal\mcapi\ParamConverter;

use Drupal\Core\Entity\EntityManager;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Route;
use Drupal\Core\ParamConverter\EntityConverter;
use Drupal\Core\ParamConverter\ParamConverterInterface;

/**
 * Provides upcasting for a view entity to be used in the Views UI.
 *
 * Example:
 *
 * pattern: '/some/{transaction_serial}'
 * options:
 *   parameters:
 *     transaction_serial:
 *       type: 'entity:mcapi_transaction'
 *       serial: TRUE
 *
 * The value for {view} will be converted to a view entity prepared for the
 * Views UI and loaded from the views temp store, but it will not touch the
 * value for {bar}.
 */
class TransactionSerialConverter extends EntityConverter implements ParamConverterInterface {

  /**
   * {@inheritdoc}
   */
  public function convert($value, $definition, $name, array $defaults, Request $request) {
    $entity_type = substr($definition['type'], strlen('entity:'));
    if ($storage = $this->entityManager->getStorageController($entity_type)) {
      $entity = $storage->loadByProperties(array('serial' => $value, 'parent' => '0'));
      if (!empty($entity)) {
        return reset($entity);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function applies($definition, $name, Route $route) {
    if (parent::applies($definition, $name, $route)) {
      return !empty($definition['serial']) && $definition['type'] === 'entity:mcapi_transaction';
    }
    return FALSE;
  }
}