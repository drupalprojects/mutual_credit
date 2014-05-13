<?php

/**
 * @file
 * Definition of Drupal\mcapi\ViewBuilder\TransactionViewBuilder.
 */

namespace Drupal\mcapi\ViewBuilder;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityViewBuilder;
use Drupal\entity\Entity\EntityDisplay;
use Drupal\Core\Template\Attribute;

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
    $build = array(
      '#theme_wrappers' => array('mcapi_transaction'),
      '#langcode' => $langcode
    );
    switch($view_mode) {
    	case 'certificate':
    	case 'operation':
        $build['#theme'] = 'certificate';
        //css helps rendering the default certificate
        $build['#attached'] = array(
          'css' => array(drupal_get_path('module', 'mcapi') .'/mcapi.css')
        );
        break;
    	case 'sentence':
        $template = \Drupal::config('mcapi.misc')->get('sentence_template');
        $build['#markup'] = \Drupal::Token()->replace(
          $template,
          array('mcapi' => $entity),
          array('sanitize' => TRUE)
        );
        break;
    	default:
        echo "is it possible to get rid of this view mode by using mcapi_render_twig_transaction directly?";
        if (module_exists('devel'))ddebug_backtrace();
        $build['#theme'] = 'mcapi_twig';
        $build['#twig'] = $view_mode;
        $view_mode == 'twig';
    }

    if ($this->viewModesInfo[$view_mode]['cache'] && !$entity->isNew() && !isset($entity->in_preview) && $this->entityInfo['render_cache']) {
      $entity_type = $this->getEntityTypeId();
      $return['#cache'] = array(
        'keys' => array('entity_view', $entity_type, $entity->id(), $view_mode),
        'granularity' => DRUPAL_CACHE_PER_ROLE,
        'bin' => $this->cacheBin,
        'tags' => array(
          $entity_type . '_view' => TRUE,
          $entity_type => array($entity->id()),
        ),
      );
    }
    $build['#mcapi_transaction'] = $entity;
    return $build;
  }

  /**
   *
   * @param TransactionInterface $transaction
   * @param string $view_mode
   *
   * @return array
   *   An array that can be processed by drupal_pre_render_links().
   */
  function renderLinks(TransactionInterface $transaction, $view_mode = 'certificate') {
    $renderable = array();
    //child transactions and unsaved transactions never show links
    if (!$transaction->get('parent')->value && $transaction->get('serial')->value) {
      $view_link = $view_mode != 'certificate';
      foreach (show_transaction_operations($view_link) as $op => $plugin) {
        if ($transaction->access($op)) {
          $renderable['#links'][$op] = array(
            'title' => $plugin->label,
            'route_name' => $op == 'view' ? 'mcapi.transaction_view' : 'mcapi.transaction.op',
            'route_parameters' => array(
              'mcapi_transaction' => $transaction->serial->value,
              'op' => $op
            )
          );
          if ($mode == 'modal') {
            $renderable['#links'][$op]['attributes']['data-accepts'] = 'application/vnd.drupal-modal';
            $renderable['#links'][$op]['attributes']['class'][] = 'use-ajax';
          }
          elseif($mode == 'ajax') {
            //I think we need a new router path for this...
            $renderable['#attached']['js'][] = 'core/misc/ajax.js';
            //$renderable['#links'][$op]['attributes']['class'][] = 'use-ajax';
          }
          else{
            $renderable['#links'][$op]['query'] = drupal_get_destination();
          }
        }
      }
      if (array_key_exists('#links', $renderable)) {
        $renderable += array(
          '#theme' => 'links',
          //'#heading' => t('Operations'),
          '#attached' => array(
            'css' => array(drupal_get_path('module', 'mcapi') .'/mcapi.css')
          ),
          //Attribute class not found
          '#attributes' => new Attribute(array('class' => array('transaction-operations'))),
        );
      }
    }
    return $renderable;
  }

}
