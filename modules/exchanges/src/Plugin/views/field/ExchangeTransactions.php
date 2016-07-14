<?php

namespace Drupal\mcapi_exchanges\Plugin\views\field;

use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\ResultRow;
use Drupal\Core\Form\FormStateInterface;

/**
 * Field handler to get the transaction count from the exchange entity.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("exchange_transactions")
 *
 * @todo This should be possible with the group module soon.
 */
class ExchangeTransactions extends FieldPluginBase {

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['uncounted'] = array('default' => TRUE);
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    $form['uncounted'] = array(
      '#title' => t("Include transactions in 'uncounted' states, e.g. erased, pending"),
      '#type' => 'checkbox',
      '#default_value' => $this->options['uncounted'],
    );
    parent::buildOptionsForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   *
   * @todo is and empty query() needed for virtual views fields?
   */
  function query(){}

  /**
   * {@inheritdoc}
   */
  public function render(ResultRow $values) {
    $states = \Drupal::config('mcapi.settings')->get('counted');
    // @todo this should be a transaction entityQuery
    // return $this->getEntity($values)->transactionCount([state => $states]);
    debug($states);
    return '*0*';
  }

}
