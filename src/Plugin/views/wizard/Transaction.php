<?php

namespace Drupal\mcapi\Plugin\views\wizard;

use Drupal\views\Plugin\views\wizard\WizardPluginBase;

/**
 * Defines a wizard for the mcapi_transaction table.
 *
 * @ViewsWizard(
 *   id = "transaction",
 *   module = "mcapi",
 *   base_table = "mcapi_transaction",
 *   title = @Translation("Transactions")
 * )
 */
class Transaction extends WizardPluginBase {

  /**
   * Set the created column.
   */
  protected $createdColumn = 'created';

}
