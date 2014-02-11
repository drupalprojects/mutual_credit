<?php

/**
 * @file
 * Definition of Drupal\mcapi\TransactionViewBuilder.
 */

namespace Drupal\mcapi;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityViewBuilder;
use Drupal\entity\Entity\EntityDisplay;

/**
 * Render controller for transactions.
 */
class TransactionViewBuilder extends EntityViewBuilder {

  /**
   * Provides entity-specific defaults to the build process.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity for which the defaults should be provided.
   * @param string $view_mode
   *   The view mode that should be used.
   * @param string $langcode
   *   (optional) For which language the entity should be prepared, defaults to
   *   the current content language.
   *
   * @return array
   */
  protected function getBuildDefaults(EntityInterface $entity, $view_mode, $langcode) {
    if ($view_mode == 'certificate') {
      $build['#theme'] = 'certificate';
      //css helps rendering the default certificate
      $build['#attached'] = array(
        'css' => array(drupal_get_path('module', 'mcapi') .'/mcapi.css')
      );
    }
    else {
      $build['#theme'] = 'mcapi_twig';
      if ($view_mode == 'sentence') {
        $build['#twig'] = \Drupal::config('mcapi.misc')->get('sentence_template');
      }
      else {
        $build['#twig'] = $view_mode;
        $view_mode = 'twig';
      }
    }

    $build['#theme_wrappers'] = array('mcapi_transaction');
    $build['#mcapi_transaction'] = $entity;
    $build['#showlinks'] = TRUE;
    $build['#langcode'] = $langcode;
    if ($this->viewModesInfo[$view_mode]['cache'] && !$entity->isNew() && !isset($entity->in_preview) && $this->entityInfo['render_cache']) {
      $return['#cache'] = array(
        'keys' => array('entity_view', $this->entityType, $entity->id(), $view_mode),
        'granularity' => DRUPAL_CACHE_PER_ROLE,
        'bin' => $this->cacheBin,
        'tags' => array(
          $this->entityType . '_view' => TRUE,
          $this->entityType => array($entity->id()),
        ),
      );
    }
    return $build;
  }

}


