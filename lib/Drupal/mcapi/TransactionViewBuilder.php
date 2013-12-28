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
 * Render controller for nodes.
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
      $build['#theme_wrappers'] = array('mcapi_transaction');
      $build['#mcapi_transaction'] = $entity;
      $build['#langcode'] = $langcode;
      //css helps rendering the default certificate
      $build['#attached'] = array(
        'css' => array(drupal_get_path('module', 'mcapi') .'/mcapi.css')
      );
    }
    else {//assume it is twig
      $build['customtwig'] = array(
        '#markup' => mcapi_render_twig_transaction($view_mode, $transaction)
      );
    }
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

  /**
   * Overrides Drupal\Core\Entity\EntityRenderController::buildContent().
   *
   * build a render array for any number of transactions
   * first arg can be one or an array of transactions, WITH CHILDREN LOADED as in transaction_load
   * $transactions an array of transactions, keyed by xid, each one having its children already loaded
   * $view mode, defaults to certificate with the saved transaction sentence, but an arbitrary token string can also be used
   */
  public function buildContent(array $transactions, array $displays, $view_mode = 'certificate', $langcode = NULL) {
    parent::buildContent($transactions, $displays, $view_mode, $langcode);
    foreach ($transactions as $transaction->xid => $transaction) {
      $transaction->content['links'] = $transaction->links('ajax', FALSE);
    }

  }

}


