<?php

/**
 * @file
 * Contains \Drupal\mcapi\Plugin\views\wizard\Transaction.
 */

namespace Drupal\mcapi\Plugin\views\wizard;

use Drupal\views\Plugin\views\wizard\WizardPluginBase;
use Drupal\views\Annotation\ViewsWizard;
use Drupal\Core\Annotation\Translation;

/**
 * Defines a wizard for the mcapi_transaction table.
 *
 * @ViewsWizard(
 *   id = "transaction",
 *   module = "mcapi",
 *   base_table = "mcapi_transactions",
 *   title = @Translation("Transactions")
 * )
 */
class Transaction extends WizardPluginBase {

  /**
   * Set the created column.
   */
  protected $createdColumn = 'created';


}
