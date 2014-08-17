<?php

/**
 * @file
 * Contains \Drupal\mcapi_tester\Plugin\views\argument\TransactionExchange.
 */

namespace Drupal\mcapi_exchanges\Plugin\views\argument;

use Drupal\views\Plugin\views\argument\Numeric;
use Drupal\Core\Form\FormStateInterface;

/**
 * Filter the transaction query to transactions in one or more exchanges
 *
 * @ingroup views_argument_handlers
 *
 * @ViewsArgument("transaction_exchange")
 */
class TransactionExchange extends Numeric {

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['inclusive'] = array('default' => TRUE);
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    $form['inclusive'] = array(
      '#title' => t('Include transactions in all states'),
      '#type' => 'checkbox',
      '#default_value' => $this->options['link'],
    );
    parent::buildOptionsForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   * @see \Drupal\views\Plugin\views\relationship\RelationshipPluginBase::query()
   * @todo tidy this up
   */
  public function query($group_by = FALSE) {
    //$this->ensureMyTable();

    $handler = $this->options['inclusive'] ? db_or() : db_and();
    //we have already joined to the mcapi_wallet_exchanges_index table
    //in mcapi_views_query_alter in order to do access control
    //the alias for that will be 'mcapi_wallet_exchanges_payer' and 'mcapi_wallet_exchanges_payee'
    $this->query->addWhere(
      0,
      $handler
        ->condition('mcapi_wallet_exchanges_payer.exid', $this->argument)
        ->condition('mcapi_wallet_exchanges_payee.exid', $this->argument)
    );
  }
}
