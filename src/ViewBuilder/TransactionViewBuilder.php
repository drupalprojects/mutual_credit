<?php

/**
 * @file
 * Definition of Drupal\mcapi\ViewBuilder\TransactionViewBuilder.
 */

namespace Drupal\mcapi\ViewBuilder;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityViewBuilder;
use Drupal\Core\Template\Attribute;
use Drupal\Component\Utility\NestedArray;
use Drupal\mcapi\Entity\TransactionInterface;

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
    //if the view_mode is 'full' that means nothing was specified, which is the norm.
    //so we turn to the 'view' transition where the view mode is a configuration.
    if ($view_mode == 'full') {
      $view_config = \Drupal::config('mcapi.transition.view');
      $view_mode = $view_config->get('format');
    }

    $build = array();
    switch($view_mode) {
    	case 'certificate':
        $build['#theme'] = 'certificate';
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
        $build['#theme'] = 'mcapi_twig';
        $build['#twig_template'] = $view_config->get('twig');
        $view_mode == 'twig';
    }

    $build += array(
      '#view_mode' => $view_mode,
      '#theme_wrappers' => array('mcapi_transaction'),
      '#langcode' => $langcode,
      '#mcapi_transaction' => $entity,
      '#attached' => array(
    	  'css' => array(drupal_get_path('module', 'mcapi') .'/css/transaction.css')
      ),
      '#cache' => array(
        'tags' =>  NestedArray::mergeDeep($this->getCacheTag(), $entity->getCacheTag()),
      ),
    );

    // Cache the rendered output if permitted by the view mode and global entity
    // type configuration.
    if ($this->isViewModeCacheable($view_mode) && !$entity->isNew() && $entity->isDefaultRevision() && $this->entityType->isRenderCacheable()) {
      $build['#cache'] += array(
        'keys' => array(
          'entity_view',
          'mcapi_transaction',
          $entity->id(),
          $view_mode,
          'cache_context.theme',
          'cache_context.user.roles',
        ),
        'bin' => 'render',//hardcoded for speed,
      );
    }
    return $build;
  }

  /**
   *
   * @param TransactionInterface $transaction
   * @param string $view_mode
   * @param string $dest_type
   *   whether the links should go to a new page, a modal box, or an ajax refresh
   *
   * @return array
   *   An array that can be processed by drupal_pre_render_links().
   */
  function renderLinks(TransactionInterface $transaction, $view_mode = 'certificate', $dest_type = NULL) {
    $renderable = array();
    //child transactions and unsaved transactions never show links
    if (!$transaction->get('parent')->value && $transaction->get('serial')->value) {
      $view_link = $view_mode != 'certificate';
      foreach (show_transaction_transitions($view_link) as $op => $plugin) {
        if ($transaction->access($op)) {
          $renderable['#links'][$op] = array(
            'title' => $plugin->label,
            'route_name' => $op == 'view' ? 'mcapi.transaction_view' : 'mcapi.transaction.op',
            'route_parameters' => array(
              'mcapi_transaction' => $transaction->serial->value,
              'op' => $op
            )
          );
          if ($dest_type == 'modal') {
            $renderable['#links'][$op]['attributes']['data-accepts'] = 'application/vnd.drupal-modal';
            $renderable['#links'][$op]['attributes']['class'][] = 'use-ajax';
          }
          elseif($dest_type == 'ajax') {
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
          //'#heading' => t('Transitions'),
          '#attached' => array(
            'css' => array(drupal_get_path('module', 'mcapi') .'/mcapi.css')
          ),
          //Attribute class not found
          '#attributes' => new Attribute(array('class' => array('transaction-transitions'))),
        );
      }
    }
    return $renderable;
  }

}
