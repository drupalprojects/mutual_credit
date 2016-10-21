<?php

namespace Drupal\mcapi\ViewBuilder;

use Drupal\system\Entity\Action;
use Drupal\mcapi\Mcapi;
use Drupal\mcapi\Plugin\TransactionActionBase;
use Drupal\mcapi\Entity\TransactionInterface;
use Drupal\Core\Utility\Token;
use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Routing\CurrentRouteMatch;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityViewBuilder;
use Drupal\Core\Url;
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
        $template = '<div class = "mcapi_transaction-sentence">' . $this->settings->get('sentence_template') . '</div>';
        $build['#markup'] = $this->token->replace($template, ['xaction' => $entity]);
        if ($view_mode == 'sentence') {
          break;
        }
        $link_list = $this->buildLinkList($entity);
        $build['#markup'] .= render($link_list);
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
        'id' => 'transaction-' . ($entity->serial->value ?: 0),
      ],
      '#attached' => [
        // For some reason in Renderer::updatestack, this bubbles up twice.
        'library' => ['mcapi/mcapi.transaction'],
      ],
    ];
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
        $build[$id]['links'] = $this->buildLinkList($transaction);
      }
    }
  }

  /**
   * Build a list of transaction operations as links.
   *
   * @param TransactionInterface $transaction
   *   The transaction to build links for.
   *
   * @return array
   *   An array of links.
   */
  public function buildLinkList(TransactionInterface $transaction) {
    $operations = [];
    foreach (Mcapi::transactionActionsLoad() as $action_name => $action) {
      $plugin = $action->getPlugin();
      if ($plugin->access($transaction)) {
        $route_params = ['mcapi_transaction' => $transaction->serial->value];
        if ($action_name == 'transaction_view') {
          $route_name = 'entity.mcapi_transaction.canonical';
        }
        else {
          $route_name = $action->getPlugin()->getPluginDefinition()['confirm_form_route_name'];
          $route_params['operation'] = substr($action_name, 12);
        }

        $operations[$action_name] = [
          'title' => $plugin->getConfiguration()['title'],
          'url' => Url::fromRoute($route_name, $route_params),
        ];

        $display = $plugin->getConfiguration('display');
        if ($display != TransactionActionBase::CONFIRM_NORMAL) {
          if ($display == TransactionActionBase::CONFIRM_MODAL) {
            $operations['#attached']['library'][] = 'core/drupal.ajax';
            $operations[$action_name]['attributes'] = [
              'class' => ['use-ajax'],
              'data-dialog-type' => 'modal',
              'data-dialog-options' => Json::encode(['width' => 500]),
            ];
          }
          elseif ($display == TransactionActionBase::CONFIRM_AJAX) {
            // To make a ajax link it seems necessary to put the url twice.
            $operations[$action_name]['ajax'] = [
              // There must be either a callback or a path.
              'wrapper' => 'transaction-' . $transaction->serial->value,
              'method' => 'replace',
              'path' => $operations[$action_name]['url']->getInternalPath(),
            ];
          }
        }
        elseif ($display != TransactionActionBase::CONFIRM_NORMAL && $action_name != 'view') {
          // The link should redirect back to the current page by default.
          if ($dest = $plugin->getConfiguration('redirect')) {
            $redirect = ['destination' => $dest];
          }
          else {
            $redirect = $this->redirecter->getAsArray();
          }
          // @todo stop removing leading slash when the redirect service does it properly
          $operations[$action_name]['query'] = $redirect;
        }
      }
    }
    $operations += $this->moduleHandler()->invokeAll('entity_operation', [$transaction]);
    $this->moduleHandler()->alter('entity_operation', $operations, $transaction);
    // @todo check the order is sensible
    uasort($operations, '\Drupal\Component\Utility\SortArray::sortByWeightElement');

    $links = [];
    if ($operations) {
      $links = [
        '#theme' => 'item_list',
        '#list_type' => 'ul',
        '#items' => [],
        '#attributes' => ['class' => ['transaction-operations']],
      ];
      foreach ($operations as $op) {
        //@todo how to incorporate the rest of the operations
        $links['#items'][] = [
          '#type' => 'link',
          '#title' => $op['title'],
          '#url' => $op['url'],
        ];
      }
    }
    return $links;
  }

}
