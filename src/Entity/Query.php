<?php

namespace Drupal\mcapi\Entity;

use Drupal\Core\Entity\Query\Sql\Query as BaseQuery;

/**
 * The SQL storage entity query class.
 */
class Query extends BaseQuery {

  /**
   * {@inheritdoc}
   */
  protected function prepare() {
    if ($this->entityTypeId == 'mcapi_transaction') {
      return $this->prepareTransaction();
    }
    else {
      return parent::prepare();
    }
  }

  /**
   * Build an entityQuery for transactions.
   *
   * @return \Drupal\mcapi\Entity\Query
   *   A transaction entityQuery object.
   *
   * @note The query returns an array of serial numbers keyed by xid
   */
  private function prepareTransaction() {
    $this->sqlQuery = $this->connection
      ->select('mcapi_transaction', 'base_table', ['conjunction' => $this->conjunction]);

    // When there is no revision support, the key field is the entity key.
    $this->sqlFields["base_table.xid"] = ['base_table', 'xid'];
    // Now add the value column for fetchAllKeyed(). This is always the
    // entity id.
    $this->sqlFields["base_table.serial" . '_1'] = ['base_table', 'serial'];

    if ($this->accessCheck) {
      $this->sqlQuery->addTag($this->entityTypeId . '_access');
    }
    $this->sqlQuery->addTag('entity_query');
    $this->sqlQuery->addTag('entity_query_' . $this->entityTypeId);

    // Add further tags added.
    if (isset($this->alterTags)) {
      foreach ($this->alterTags as $tag => $value) {
        $this->sqlQuery->addTag($tag);
      }
    }

    // Add further metadata added.
    if (isset($this->alterMetaData)) {
      foreach ($this->alterMetaData as $key => $value) {
        $this->sqlQuery->addMetaData($key, $value);
      }
    }
    // This now contains first the table containing entity properties and
    // last the entity base table. They might be the same.
    $this->sqlQuery->addMetaData('entity_type', $this->entityTypeId);
    $this->sqlQuery->addMetaData('simple_query', TRUE);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function condition($property, $value = NULL, $operator = NULL, $langcode = NULL) {
    // Need to add some special conditions.
    switch ($property) {
      case 'involving':
        $group = $this->orConditionGroup()
          ->condition('payer', (array) $value, 'IN')
          ->condition('payee', (array) $value, 'IN');
        $this->condition($group);
        break;

      default:
        // Copied from the parent.
        $this->condition->condition($property, $value, $operator);
    }

    return $this;
  }

}
