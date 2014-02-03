<?php

/**
 * @file
 * Definition of Drupal\mcapi\Plugin\views\field\UserStats
 */

namespace Drupal\mcapi\Plugin\views\field;

use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\Component\Annotation\PluginID;
use Drupal\views\ResultRow;

/**
 * Field handler to present trading stats for the user
 *
 * @ingroup views_field_handlers
 *
 * @PluginID("mcapi_userstat")
 */
class UserStats extends FieldPluginBase {

  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['separator'] = array('default' => '');
    $options['stat'] = array('default' => 'balance');
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, &$form_state) {
    $form['separator'] = array(
      '#title' => t('Separator between different stats'),
      '#type' => 'textfield',
      '#size' => 10,
      '#default_value' => $this->options['separator'],
    );
    $form['stat'] = array(
      '#title' => t('Metric'),
      '#type' => 'radios',
      '#options' => array(
        'balance' => t('Balance'),
        'gross_in' => t('Gross income'),
        'gross_out' => t('Gross expenditure'),
        'volume' => t('Volume (Gross in + gross out)'),
        'trades' => t('Balance'),
        'partners' => t('Number of trading partners'),
      ),
      '#default_value' => $this->options['stat'],
    );
    parent::buildOptionsForm($form, $form_state);
  }
  /**
   * {@inheritdoc}
   */
  public function query() {
    $this->addAdditionalFields();
  }

  function render(ResultRow $values) {
    $account = $this->getEntity($values);
    $wid = reset(mcapi_get_wallet_ids($account));//shows only the first wallet
    $exchanges = referenced_exchanges($account);
    //this isn't going to work...
    //@todo make this work with the right entity_reference syntax
    $currency = reset($exchanges)->field_currencies->getvalue(TRUE)->entity;

    $result = \Drupal::entityManager()->getStorageController('mcapi_transaction')->summaryData(
      entity_load('mcapi_wallet', $wid),
      $currency
    );

    if (in_array($this->options['stat'], array('trades', 'partners'))) {
      return $result[$this->options['stat']];
    }
    else return $currency->format($result[$this->options['stat']]);

  }

}
