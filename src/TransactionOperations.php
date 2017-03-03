<?php

namespace Drupal\mcapi;

use Drupal\mcapi\Entity\TransactionInterface;
use Drupal\mcapi\Plugin\TransactionActionBase;
use Drupal\system\Entity\Action;
use Drupal\Core\Url;

/**
 * Handle the transaction entity's 'operations' which are in fact system actions
 * with extra config. Most operations lead to a highly configured confirm form,
 * which can be reached though ajax, a modal box or in a new page.
 * View is special because it has no confirm form and indeed no action, but
 * shares access control and page formatting with the other operations. Save is
 * also special, being hidden from the UI.
 *
 * @todo make this a service? What are the advantages?
 */
class TransactionOperations {

  /**
   * Get the operations for the given transaction
   *
   * @param TransactionInterface $transaction
   *
   * @return array
   *   A renderable array
   *
   * @todo cache these by user and transaction
   */
  public static function get(TransactionInterface $transaction) {
    $operations = [];

    foreach (static::loadAllActions() as $action_name => $action) {
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
            $operations[$action_name]['url']['attributes'] = [
              'class' => ['use-ajax'],
              'data-dialog-type' => 'modal',
              'data-dialog-options' => Json::encode(['width' => 500]),
            ];
          }
          elseif ($display == TransactionActionBase::CONFIRM_AJAX) {
            // To make a ajax link it seems necessary to put the url twice.
            $operations[$action_name]['url']['ajax'] = [
              // There must be either a callback or a path.
              'wrapper' => 'transaction-'. $transaction->serial->value,
              'method' => 'replace',
              'path' => $operations[$action_name]['url']->getInternalPath(),
            ];
          }
        }
        elseif ($display != TransactionActionBase::CONFIRM_NORMAL and $action_name != 'view') {
          // The link should redirect back to the current page by default.
          if ($dest = $plugin->getConfiguration('redirect')) {
            $redirect = ['destination' => $dest];
          }
          else {
            $redirect = \Drupal::service('redirect.destination')->getAsArray();
          }
          // @todo stop removing leading slash when the redirect service does it properly
          $operations[$action_name]['url']['query'] = $redirect;
        }
      }
    }
    // These are core hooks
    $operations += \Drupal::moduleHandler()->invokeAll('entity_operation', [$transaction]);
    \Drupal::moduleHandler()->alter('entity_operation', $operations, $transaction);
    // @todo check the order is sensible
    uasort($operations, '\Drupal\Component\Utility\SortArray::sortByWeightElement');
    return $operations;
  }


  /**
   * Utility function.
   *
   * Loads any of the transaction operation actions.
   *
   * @param string $operation
   *   May or may not begin with transaction_'.
   *
   * @todo alter the incoming $operation to be more consistent and harmonise
   * with static::loadAllActions().
   */
  public static function loadOperation($operation) {
    // Sometimes the $operation is from the url so it is shortened, and
    // sometimes is the id of an action. There is a convention that all
    // transaction actions take the form transaction_ONEWORD take the oneword
    // from the given path.
    if ($operation == 'operation') {
      $operation = \Drupal::routeMatch()->getParameter('operation');
    }
    if (substr($operation, 0, 12) != 'transaction_') {
      $action_name = 'transaction_' . $operation;
    }
    else {
      $action_name = $operation;
    }
    if ($action = Action::load($action_name)) {
      return $action;
    }
  }

  /**
   * Utility function.
   *
   * Load those special transaction operations which are also actions.
   */
  public static function loadAllActions() {
    return \Drupal::entityTypeManager()
      ->getStorage('action')
      ->loadByProperties(['type' => 'mcapi_transaction']);
  }
}