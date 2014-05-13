<?php

/**
 * @file
 * Definition of Drupal\mcapi\Plugin\views\field\ExchangeTransactions.
 */

namespace Drupal\mcapi\Plugin\views\field;

use Drupal\views\Plugin\views\area\Result;
use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\ResultRow;
use Drupal\Component\Annotation\PluginID;

/**
 * Field handler to get the transaction count from the exchange entity
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("exchange_transactions")
 */
class ExchangeTransactions extends FieldPluginBase {


  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['since'] = array('default' => '');
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, &$form_state) {
    $form['since'] = array(
      '#title' => t('Time period'),
      '#title' => t('Relative time. PhP strtotime format. E.g. -365 days'),
      '#type' => 'textfield',
      '#size' => 10,
      '#default_value' => $this->options['since'],
    );
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
    //$this->ensureMyTable();
  }

  /**
   * {@inheritdoc}
   */
  public function render(ResultRow $values) {
    return $this->getEntity($values)->transactions($this->options['since']);
  }

}
