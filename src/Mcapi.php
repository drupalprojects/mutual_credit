<?php

namespace Drupal\mcapi;

use Drupal\system\Entity\Action;
use Drupal\Core\Url;
use Drupal\Core\Link;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Component\Utility\Html;

/**
 * Utility class for community accounting.
 */
class Mcapi {

  /**
   * Get a list of bundles which can have wallets.
   *
   * Which is to say, whether it is a contentEntity configured to have at least
   * 1 wallet.
   *
   * @return array
   *   Bundle names keyed by entity type ids.
   */
  public static function walletableBundles($reset = FALSE) {
    $bundles = &drupal_static(__FUNCTION__);
    if (!is_array($bundles) || $reset) {
      if ($cache = \Drupal::cache()->get('walletableBundles')) {
        $bundles = $cache->data;
      }
      else {
        $bundles = [];
        $types = \Drupal::Config('mcapi.settings')->get('entity_types');
        // Having some problems on installation
        if (!$types) {
          // So we don't cache nothing.
          return [];
        }
        foreach (\Drupal::Config('mcapi.settings')->get('entity_types') as $entity_bundle => $max) {
          if (!$max) {
            continue;
          }
          list($type, $bundle) = explode(':', $entity_bundle);
          $bundles[$type][$bundle] = $max;
        }
        \Drupal::cache()->set(
          'walletableBundles',
          $bundles,
          CacheBackendInterface::CACHE_PERMANENT,
          // @todo what are the tags?
          ['walletable_bundles']
        );
      }
    }
    return $bundles;
  }

  /**
   * Find the maximum number of wallets a bundle can have.
   *
   * @param string $entity_type_id
   *   The machine name of the entity type.
   * @param string $bundle
   *   The machine name of the bundle.
   *
   * @return int
   *   The maximum number of wallets.
   */
  public static function maxWalletsOfBundle($entity_type_id, $bundle = NULL) {
    if (!$bundle) {
      $bundle = $entity_type_id;
    }
    $bundles = static::walletableBundles();
    if (isset($bundles[$entity_type_id])) {
      if (isset($bundles[$entity_type_id][$bundle])) {
        return $bundles[$entity_type_id][$bundle];
      }
    }
    return 0;
  }

  /**
   * Uasort callback for configuration entities.
   *
   * Should have been included in Drupal Core?
   */
  public static function uasortWeight($a, $b) {
    $a_weight = (is_object($a) && property_exists($a, 'weight')) ? $a->weight : 0;
    $b_weight = (is_object($b) && property_exists($b, 'weight')) ? $b->weight : 0;
    if ($a_weight == $b_weight) {
      return 0;
    }
    return ($a_weight < $b_weight) ? -1 : 1;
  }

  /**
   * Utility function to populate a form widget's options with entity names.
   *
   * @param string $entity_type_id
   *   The machine name of the entity type.
   * @param array $data
   *   Either entities of the given type, entity ids, or $conditions for
   *   entity_load_multiple_by_properties.
   *
   * @return string[]
   *   The entity names, keyed by entity id.
   *
   * @see Drupal\Core\Entity\Plugin\EntityReferenceSelection::getReferenceableEntities
   */
  public static function entityLabelList($entity_type_id, array $data = []) {
    if (empty($data)) {
      $entities = \Drupal::entityTypeManager()->getStorage($entity_type_id)->loadMultiple();
    }
    elseif (is_string(key($data))) {
      // That means it is a conditions list.
      $entities = entity_load_multiple_by_properties($entity_type_id, $data);
    }
    elseif (is_numeric(reset($data))) {
      $entities = \Drupal::entityTypeManager()->getStorage($entity_type_id)->loadMultiple($data);
    }
    else {
      $entities = $data;
    }
    if (property_exists(current($entities), 'weight') && count($entities) > 1) {
      uasort($entities, '\Drupal\mcapi\Mcapi::uasortWeight');
    }
    $list = [];
    foreach ($entities as $entity) {
      $list[$entity->id()] = Html::escape(\Drupal::entityManager()->getTranslationFromContext($entity)->label());
    }
    return $list;
  }

  /**
   * Service container.
   *
   * @return \Drupal\mcapi\Plugin\TransactionRelativeManager
   *   The 'Transaction relative manager' service.
   */
  public static function transactionRelatives($plugin_names = []) {
    return \Drupal::service('mcapi.transaction_relative_manager')->activatePlugins($plugin_names);
  }

  /**
   * Get a list of all the tokens on the transaction entity.
   *
   * @note This really needs replacing with a core function.
   */
  public static function tokenHelp() {
    foreach (array_keys(\Drupal::Token()->getInfo()['tokens']['xaction']) as $token) {
      $tokens[] = "[xaction:$token]";
    }
    return implode(', ', $tokens);
  }

  /**
   * Get a string describing where to get help writing Twig.
   */
  public static function twigHelp() {
    foreach (array_keys(\Drupal::Token()->getInfo()['tokens']['xaction']) as $token) {
      $tokens[] = '{{ ' . $token . '}}';
    }
    // @todo how to place links in $element['#description']?
    $link = Link::fromTextAndUrl(
        t('What is twig?'),
        Url::fromUri('http://twig.sensiolabs.org/doc/templates.html')
      );
    return implode(', ', $tokens) . '. ' . $link->toString();
  }

}
