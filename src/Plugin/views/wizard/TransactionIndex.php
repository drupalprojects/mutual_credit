<?php

namespace Drupal\mcapi\Plugin\views\wizard;

use Drupal\views\Plugin\views\wizard\WizardPluginBase;

/**
 * Defines a wizard for the mcapi_transaction table.
 *
 * @ViewsWizard(
 *   id = "transaction_index",
 *   module = "mcapi",
 *   base_table = "mcapi_transactions_index",
 *   title = @Translation("Transaction index")
 * )
 */
class TransactionIndex extends WizardPluginBase {

  /**
   * Set the created column.
   */
  protected $createdColumn = 'created';

}
