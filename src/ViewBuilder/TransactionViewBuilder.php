<?php

namespace Drupal\mcapi\ViewBuilder;

use Drupal\mcapi\TransactionOperations;
use Drupal\system\Entity\Action;
use Drupal\Core\Link;
use Drupal\Core\Utility\Token;
use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Routing\CurrentRouteMatch;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityViewBuilder;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Render controller for transactions.
 */
class TransactionViewBuilder extends EntityViewBuilder {

  protected $settings;
  protected $routeMatch;
  protected $token;

  /**
   * Constructor.
   */
  public function __construct($entity_type, $entity_manager, $language_manager, ConfigFactory $config_factory, CurrentRouteMatch $route_match, Token $token) {
    parent::__construct($entity_type, $entity_manager, $language_manager);
    $this->settings = $config_factory->get('mcapi.settings');
    $this->routeMatch = $route_match;
    $this->token = $token;
  }

  /**
   * {@inheritdoc}
   *
   * @todo update with entity_type.manager when core interface changes
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    return new static(
      $entity_type,
      $container->get('entity.manager'),
      $container->get('language_manager'),
      $container->get('config.factory'),
      $container->get('current_route_match'),
      $container->get('token')
    );
  }

  /**
   * {@inheritdoc}
   *
   * 3 reasons for NOT caching transactions:
   * - it was caching twice with different contexts I couldn't find out why
   * - was tricky separating certificate caching from links in #theme_wrapper
   * - transactions are not viewed very often, more usually with views.
   */
  protected function getBuildDefaults(EntityInterface $entity, $view_mode) {
    $build = parent::getBuildDefaults($entity, $view_mode);
    switch ($view_mode) {
      case 'full':
        $view_mode = 'certificate_ops';
      case 'certificate_ops':
      case 'certificate':
        $build['#theme'] = 'mcapi_transaction_twig';
        $build['#mcapi_transaction']->twig = Action::load('transaction_view')
          ->getPlugin()
          ->getConfiguration()['twig'];
        $build['#theme_wrappers'][] = 'mcapi_transaction';
        break;

      case 'sentence_ops':
      case 'sentence':
        // This way of doing it we can't quite use the parent theme callback
        // mcapi_transaction, so we'll just add this div by hand.
        unset($build['#theme'], $build['#mcapi_transaction']);
        $build['#prefix'] = '<div class = "mcapi_transaction-sentence">';
        $build['#suffix'] = '</div>';
        $template = $this->settings->get('sentence_template');
        $build['#markup'] = $this->token->replace($template, ['xaction' => $entity]);
        if ($view_mode == 'sentence') {
          break;
        }
        $build['link_list'] = $this->linkList($entity);
        break;

      default:
        throw new Exception('unknown view mode');
    }
    $build += [
      '#attributes' => [
        'class' => [
          'transaction',
          'type-' . $entity->type->target_id,
          'state-' . $entity->state->target_id,
        ],
      ],
      '#attached' => [
        // For some reason in Renderer::updatestack, this bubbles up twice.
        'library' => ['mcapi/mcapi.transaction'],
      ],
    ];
    if (!$entity->isNew()) {
       $build['#attributes']['class']['id'] = 'transaction-'.$entity->serial->value;
    }
    unset($build['#cache']);
    // @todo we might need to use the post-render cache to get the links right instead of template_preprocess_mcapi_transaction
    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function buildComponents(array &$build, array $entities, array $displays, $view_mode) {
    parent::buildComponents($build, $entities, $displays, $view_mode);
    foreach ($entities as $id => $transaction) {
      // No action links on the action page itself
      // todo inject routeMatch //need to cache by route in that case.
      if ($this->routeMatch->getRouteName() != 'mcapi.transaction.operation' && $transaction->id()) {
        $build[$id]['links'] = $this->linkList($transaction);
      }
    }
  }

  /**
   *
   * @param Transaction $transaction
   * @return array
   *   A renderable array
   */
  private function linkList($transaction) {
    $output = $links = [];
    foreach (TransactionOperations::linkList($transaction) as $link) {
      // TODO what about the other properties in $data?
      $links[] = Link::fromTextAndUrl($link['title'], $link['url']);
    }
    if ($links) {
      $output = [
        '#theme' => 'item_list',
        '#type' => 'ul',
        '#items' => $links,
        '#attributes' => ['class' => ['transaction-operations']],
      ];
    }
    return $output;
  }

}
