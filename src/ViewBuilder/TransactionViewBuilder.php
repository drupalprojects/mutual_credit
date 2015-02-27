<?php

/**
 * @file
 * Definition of Drupal\mcapi\ViewBuilder\TransactionViewBuilder.
 */

namespace Drupal\mcapi\ViewBuilder;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Entity\EntityViewBuilder;
use Drupal\Core\Template\Attribute;
use Drupal\Component\Utility\NestedArray;
use Drupal\mcapi\TransactionInterface;
use Drupal\Core\Render\Element;
use Drupal\Core\Url;

/**
 * Render controller for transactions.
 */
class TransactionViewBuilder extends EntityViewBuilder {

  private $transitionManager;

  public function __construct(EntityTypeInterface $entity_type, EntityManagerInterface $entity_manager, LanguageManagerInterface $language_manager) {
    $this->transitionManager = \Drupal::service('mcapi.transitions');
    parent::__construct($entity_type, $entity_manager, $language_manager);
  }

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
    $build = [];
    //most entity types would build the render array for theming here.
    //however the sentence and twig view modes don't need the theming flexibility
    //even the certificate is rarely used..
    switch($view_mode) {
      case 'certificate':
        $build['#theme'] = 'certificate';
        break;
      case 'sentence':
        $build['#markup'] = \Drupal::Token()->replace(
          \Drupal::config('mcapi.misc')->get('sentence_template'),
          ['mcapi' => $entity],
          ['sanitize' => TRUE]
        );
        break;
      default:
        $build['#markup'] = mcapi_render_twig_transaction($view_config->get('twig'), $entity);
    }

    $build += array(
      '#view_mode' => $view_mode,
      '#theme_wrappers' => ['mcapi_transaction'],
      '#langcode' => $langcode,
      '#mcapi_transaction' => $entity,
      '#attached' => ['library' => ['mcapi/mcapi.transaction']],
      '#cache' => [
        'tags' =>  NestedArray::mergeDeep(
          $this->getCacheTags(), //['mcapi_transaction_view']
          $entity->getCacheTags()//['mcapi_transaction:']
        ),
      ],
    );
    drupal_set_message("The #attached libary mcapi/mcapi.transaction isn't being added in TransactionViewBuilder::getBuildDefaults()", 'warning');
    //TODO because the transition links are very contextual
    //we can't cache the transactions without a new preview view_mode which shows no links
    //Ideally we would cache the certificate but NOT the wrapped certificate
    /*
    if ($this->isViewModeCacheable($view_mode) && !$entity->isNew() && $this->entityType->isRenderCacheable()) {
      $build['#cache'] += array(
        'keys' => array(
          'entity_view',
          'mcapi_transaction',
          $entity->serial->value,
          $view_mode,
          'cache_context.theme',
          'cache_context.language',
          'cache_context.user',
        ),
        'bin' => 'render',//hardcoded for speed,
      );
    }
     * 
     */
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
    $renderable = [];
    //child transactions and unsaved transactions never show links
    if (!$transaction->parent->value && $transaction->serial->value) {
      $exclude = ['create'];
      //TODO this is inelegant. We need to remove view when the current url is NOT the canonical url
      //OR we need to NOT render links when the transaction is being previewed
      if ($view_mode == 'certificate') $exclude[] = 'view';
      foreach ($this->transitionManager->active($exclude, $transaction->worth) as $transition => $plugin) {
        if ($transaction->access($transition)) {
          $route_name = $transition == 'view' ? 'entity.mcapi_transaction.canonical' : 'mcapi.transaction.op';
          $renderable['#links'][$transition] = array(
            'title' => $plugin->label,
            'url' => Url::fromRoute($route_name, array(
              'mcapi_transaction' => $transaction->serial->value,
              'transition' => $transition
            ))
          );
          if ($dest_type == 'modal') {
            $renderable['#links'][$transition]['attributes'] = new Attribute(
              array('data-accepts' => 'application/vnd.drupal-modal', 'class' => array('use-ajax'))
            );
          }
          elseif($dest_type == 'ajax') {
            debug('ajax mode needs work...');
            $renderable['#attached']['library'][] = 'drupal.ajax';
            //$renderable['#links'][$op]['attributes'] = new Attribute(array('class' => array('use-ajax')));
          }
          elseif(!$plugin->getConfiguration('redirect')){
            if ($transition != 'view') {
              $renderable['#links'][$transition]['query'] = drupal_get_destination();
            }
          }
        }
      }
      if (array_key_exists('#links', $renderable)) {
        $renderable += array(
          '#theme' => 'links',
          '#attached' => array('library' => array('mcapi/mcapi.transaction')),
          '#attributes' => new Attribute(array('class' => array('transaction-transitions'))),
          '#cache' => []//TODO prevent this from being ever cached
        );
      }
      drupal_set_message("The #attached libary mcapi/mcapi.transaction isn't being added in TransactionViewBuilder::getBuildDefaults()", 'warning');
    }
    return $renderable;
  }

}


