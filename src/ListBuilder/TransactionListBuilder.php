<?php

namespace Drupal\mcapi\ListBuilder;

use Drupal\mcapi\Mcapi;
use Drupal\mcapi\Plugin\TransactionActionBase;
use Drupal\mcapi\Entity\TransactionInterface;
use Drupal\Core\Url;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Entity\EntityInterface;

/**
 * Build a listing of transactions.
 *
 * @ingroup entity_api
 */
class TransactionListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'mcapi_transaction_collection';
  }

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {

    $header['serial'] = '';
    $header['created'] = $this->t('Created');
    $header['payer'] = [
      'data' => $this->t('Payer'),
      'class' => [RESPONSIVE_PRIORITY_MEDIUM],
    ];
    $header['payee'] = [
      'data' => $this->t('Payee'),
      'class' => [RESPONSIVE_PRIORITY_MEDIUM],
    ];
    $header['description'] = $this->t('Description');
    $header['worth'] = $this->t('Value(s)', [], ['context' => 'ledger entry']);
    $header['type'] = [
      'data' => $this->t('Type'),
      'class' => [RESPONSIVE_PRIORITY_LOW],
    ];
    $header['state'] = [
      'data' => $this->t('State'),
      'class' => [RESPONSIVE_PRIORITY_LOW],
    ];
    return $header + parent::buildHeader();
  }
    /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    $actions = parent::buildRow($entity);
    if (empty($actions)) {
      return;
    }
    $row['serial'] = '#'.$entity->serial->value;
    $row['created']['data'] = $entity->created->view(['label' => 'hidden']);
    $row['payer']['data'] = $entity->payer->entity->toLink();
    $row['payee']['data'] = $entity->payee->entity->toLink();
    $row['description']['data'] = $entity->toLink($entity->description->value);
    $row['worth']['data'] = $entity->worth->view(['label' => 'hidden']);
    $row['type']['data'] = \Drupal\mcapi\Entity\Type::load($entity->type->target_id)->label();
    $row['state']['data'] = \Drupal\mcapi\Entity\State::load($entity->state->target_id)->label();
    return $row + $actions;
  }


  /**
   * {@inheritdoc}
   */
  public function buildOperations(EntityInterface $entity) {
    $build = array(
      '#type' => 'operations',
      '#links' => $this->getOperations($entity),
      // Same as parent but without caching.
      '#cache' => [],
    );
    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function getOperations(EntityInterface $entity) {
    $operations = [];
    foreach (Mcapi::transactionActionsLoad() as $action_name => $action) {
      $plugin = $action->getPlugin();
      if ($plugin->access($entity)) {
        $route_params = ['mcapi_transaction' => $entity->serial->value];
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
              'wrapper' => 'transaction-' . $entity->serial->value,
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
    $operations += $this->moduleHandler()->invokeAll('entity_operation', [$entity]);
    $this->moduleHandler()->alter('entity_operation', $operations, $entity);
    // @todo check the order is sensible
    uasort($operations, '\Drupal\Component\Utility\SortArray::sortByWeightElement');
    return $operations;
  }



}
