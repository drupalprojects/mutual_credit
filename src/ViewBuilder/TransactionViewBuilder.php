<?php

/**
 * @file
 * Definition of Drupal\mcapi\ViewBuilder\TransactionViewBuilder.
 */

namespace Drupal\mcapi\ViewBuilder;

use Drupal\mcapi\Mcapi;
use Drupal\mcapi\Plugin\TransactionActionBase;
use Drupal\mcapi\Entity\TransactionInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityViewBuilder;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Render controller for transactions.
 */
class TransactionViewBuilder extends EntityViewBuilder {

  private $settings;

  public function __construct($entity_type, $entity_manager, $language_manager, $config_factory) {
    $this->settings = $config_factory->get('mcapi.settings');
    parent::__construct($entity_type, $entity_manager, $language_manager);
  }

  /**
   * {@inheritdoc}
   * @todo update with entity_type.manager when core interface changes
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    return new static(
      $entity_type,
      $container->get('entity.manager'),
      $container->get('language_manager'),
      $container->get('config.factory')
    );
  }

  /**
   * Provides entity-specific defaults to the build process.
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
  protected function getBuildDefaults(EntityInterface $entity, $view_mode) {
    //if the view_mode is 'full' that means nothing was specified, which is the norm.
    //so we turn to the 'view' transition where the view mode is a configuration.
    $build = parent::getBuildDefaults($entity, $view_mode);
    switch($view_mode) {
      case 'full':
      case 'certificate':
        $build['#theme'] = 'mcapi_transaction_twig';
        $build['#mcapi_transaction']->twig = \Drupal\system\Entity\Action::load('transaction_view')
          ->getPlugin()
          ->getConfiguration()['twig'];
        $build['#theme_wrappers'][] = 'mcapi_transaction';
        break;
      case 'sentence':
        //this way of doing it we can't quite use the parent theme callback mcapi_transaction
        //so we'll just add this div by hand
        $template = '<div class = "mcapi_transaction-sentence">'.$this->settings->get('sentence_template').'</div>';
        $build = ['#markup' => \Drupal::token()->replace($template, ['xaction' => $entity])];
        break;
      default:
        throw new Exception('unknown view mode');
    }
    $build += [
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
    //@todo we might need to use the post-render cache to get the links right instead of template_preprocess_mcapi_transaction
    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function buildComponents(array &$build, array $entities, array $displays, $view_mode) {
    parent::buildComponents($build, $entities, $displays, $view_mode);
    foreach ($entities as $id => $transaction) {
      //no action links on the action page itself
      //todo inject routeMatch
      if (\Drupal::routeMatch()->getRouteName() != 'mcapi.transaction.operation') {
        $links = $this->buildActionlinks($transaction);
        if ($links) {
          foreach ($links as $op) {
            $items[] = [
              '#type' => 'link',
              '#title' => $op['title'],
              '#url' => $op['url']
            ];
          }
          $build[$id]['links'] = [
            //'#title' => t('Operations...'),
            '#theme' => 'item_list',
            '#list_type' => 'ul',
            '#items' => $items,
            '#attributes' => ['class' => ['transaction-operations']]
          ];
        }
      }
    }
  }

  /**
   *
   * @param TransactionInterface $transaction
   *
   * @return array
   *   A renderable array of links
   */
  function buildActionlinks(TransactionInterface $transaction) {
    $operations = [];

    foreach (Mcapi::transactionActionsLoad() as $action_name => $action) {
      $plugin = $action->getPlugin();
      if ($plugin->access($transaction, \Drupal::currentUser())) {
        $route_params = ['mcapi_transaction' => $transaction->serial->value];
        if ($action_name == 'transaction_view') {
          $route_name = 'entity.mcapi_transaction.canonical';
        }
        else {
          $route_name = $action->getPlugin()->getPluginDefinition()['confirm_form_route_name'];
          $route_params['operation'] = substr($action_name, 12);
        }

        //there is a way of doing this for actions which might yield a different URL
        $operations[$action_name] = [
          'title' => $plugin->getConfiguration()['title'],
          'url' => Url::fromRoute($route_name, $route_params)
        ];


        $display = $plugin->getConfiguration('display');
        if ($display != TransactionActionBase::CONFIRM_NORMAL) {
          if ($display == TransactionActionBase::CONFIRM_MODAL) {
            $renderable['#attached']['library'][] = 'core/drupal.ajax';
            $operations[$action_name]['attributes'] = [
              'class' => ['use-ajax'],
              'data-dialog-type' => 'modal',
              'data-dialog-options' => Json::encode(['width' => 500]),
            ];
          }
          elseif($display == TransactionActionBase::CONFIRM_AJAX) {
            //curious how, to make a ajax link it seems necessary to put the url in 2 places
            $operations[$action_name]['ajax'] = [
              //there must be either a callback or a path
              'wrapper' => 'transaction-'.$transaction->serial->value,
              'method' => 'replace',
              'path' => $operations[$action_name]['url']->getInternalPath()
            ];
          }
        }
        elseif ($display != TransactionActionBase::CONFIRM_NORMAL && $action_name != 'view') {
          //the link should redirect back to the current page, if not otherwise stated
          if($dest = $plugin->getConfiguration('redirect')) {
            $redirect = ['destination' => $dest];
          }
          else {
            $redirect = $this->redirecter->getAsArray();
          }
          //@todo stop removing leading slash when the redirect service does it properly
          $operations[$action_name]['query'] = $redirect;
        }
      }
    }
    $operations += \Drupal::moduleHandler()->invokeAll('entity_operation', [$transaction]);
    \Drupal::moduleHandler()->alter('entity_operation', $operations, $transaction);
    //@todo check the order is sensible
    uasort($operations, '\Drupal\Component\Utility\SortArray::sortByWeightElement');
    return $operations;
  }



}


