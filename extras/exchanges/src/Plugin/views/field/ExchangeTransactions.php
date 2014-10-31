<?php

/**
 * @file
 * Definition of Drupal\mcapi_exchanges\Plugin\views\field\ExchangeTransactions.
 */

namespace Drupal\mcapi_exchanges\Plugin\views\field;

use Drupal\views\Plugin\views\area\Result;
use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\ResultRow;
use Drupal\Component\Annotation\PluginID;
use Drupal\Core\Form\FormStateInterface;

/**
 * Field handler to get the transaction count from the exchange entity
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("exchange_transactions")
 */
class ExchangeTransactions extends FieldPluginBase {

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

  function query(){}

  /**
   * {@inheritdoc}
   */
  public function render(ResultRow $values) {
    $states = \Drupal::config('mcapi.misc')->get('counted');
    return $this->getEntity($values)->transactions($this->options['inclusive']);
  }

}
