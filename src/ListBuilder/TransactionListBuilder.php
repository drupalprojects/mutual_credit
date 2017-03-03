<?php

namespace Drupal\mcapi\ListBuilder;

use \Drupal\mcapi\Entity\Type;
use \Drupal\mcapi\Entity\State;
use Drupal\mcapi\Element\WorthsView;
use Drupal\mcapi\TransactionOperations;
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
    if ($actions = parent::buildRow($entity)) {
      $row['serial'] = '#'.$entity->serial->value;
      $row['created']['data'] = $entity->created->view(['label' => 'hidden']);
      $row['payer']['data'] = $entity->payer->entity->toLink();
      $row['payee']['data'] = $entity->payee->entity->toLink();
      $row['description']['data'] = $entity->toLink($entity->description->value);
      $row['worth']['data'] = $entity->worth->view(['label' => 'hidden', 'context' => WorthsView::MODE_TRANSACTION]);
      $row['type']['data'] = Type::load($entity->type->target_id)->label();
      $row['state']['data'] = State::load($entity->state->target_id)->label();
      $row += $actions;
    }
    return $actions;
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
    return TransactionOperations::get($entity);
  }


}
