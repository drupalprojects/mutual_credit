<?php

/**
 * @file
 * Contains \Drupal\mcapi\Entity\Query.
 *
 * @todo decide whether to separate this into one for each entityType
 */

namespace Drupal\mcapi\Entity;

/**
 * The SQL storage entity query class.
 */
class Query extends \Drupal\Core\Entity\Query\Sql\Query {

  /**
   * {@inheritdoc}
   */
  protected function prepare() {
    if ($this->entityTypeId == 'mcapi_transaction') {
      return $this->prepareTransaction();
    }
    else return parent::prepare();
  }

  /**
   *
   * @return \Drupal\mcapi\Entity\Query
   * @note The query returns an array of serial numbers keyed by xid
   */
  private function prepareTransaction() {
    $base_table = 'mcapi_transaction';
    $this->sqlQuery = $this->connection
      ->select($base_table, 'base_table', ['conjunction' => $this->conjunction]);

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
    $this->sqlQuery->addMetaData('all_revisions', $this->allRevisions);
    $this->sqlQuery->addMetaData('simple_query', TRUE);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function condition($property, $value = NULL, $operator = NULL, $langcode = NULL) {
    //need to add some special conditions
    switch ($property) {
      case 'involving':
        $group = $this->orConditionGroup()
          ->condition('payer', (array)$value, 'IN')
          ->condition('payee', (array)$value, 'IN');
        $this->condition($group);
        break;

      default:
        //copied from the parent
        $this->condition->condition($property, $value, $operator);
    }

    return $this;
  }

}
