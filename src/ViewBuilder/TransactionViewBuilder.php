<?php

/**
 * @file
 * Definition of Drupal\mcapi\ViewBuilder\TransactionViewBuilder.
 */

namespace Drupal\mcapi\ViewBuilder;

use Drupal\mcapi\TransactionInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Entity\EntityViewBuilder;
use Drupal\Core\Template\Attribute;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Url;

/**
 * Render controller for transactions.
 */
class TransactionViewBuilder extends EntityViewBuilder {


  public function __construct(EntityTypeInterface $entity_type, EntityManagerInterface $entity_manager, LanguageManagerInterface $language_manager) {
    $this->config = \Drupal::config('mcapi.transition.view');
    parent::__construct($entity_type, $entity_manager, $language_manager);
  }

  /**
   * Provides entity-specific defaults to the build process.\
   * 3 reasons for NOT caching transactions:
   * - it was caching twice with different contexts I couldn't find out why
   * - it was tricky separating the certificate caching from the links in the #theme_wrapper
   * - transactions are not viewed very often, more usually with views
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
      $view_mode = $this->config->get('format');
    }
    $build = parent::getBuildDefaults($entity, $view_mode, $langcode);
    switch($view_mode) {
      case 'certificate':
        $build['#theme'] = 'certificate';
        break;
      case 'sentence':
        $template = \Drupal::config('mcapi.misc')->get('sentence_template');
        $build['transaction']['#markup'] = \Drupal::Token()->replace(
          $template,
          ['mcapi' => $entity],
          ['sanitize' => TRUE]
        );
        break;
      default:
        module_load_include('inc', 'mcapi', 'src/ViewBuilder/theme');
        $build['transaction'] = [
          '#type' => 'inline_template',
          '#template' => _filter_autop($this->config->get('twig')),
          '#context' => get_transaction_vars($entity)
        ];
    }
    $build += [
      '#theme_wrappers' => ['mcapi_transaction'],
      '#attributes' => [
        'class' => [
          'transaction',
          'type-'.$entity->type->target_id,
          'state-' . $entity->state->target_id
        ],
        'id' => 'transaction-'. ($entity->serial->value ? : 0),
      ],
      '#attached' => [
        //for some reason in Renderer::updatestack, this bubbles up twice
        'library' => ['mcapi/mcapi.transaction']
      ]
    ];
    unset($build['#cache']);
    return $build;
  }



  /**
   * {@inheritdoc}
   */
  public function getCacheTags() {
    return[];
    return ['mcapi_transaction_view'];
  }
}


