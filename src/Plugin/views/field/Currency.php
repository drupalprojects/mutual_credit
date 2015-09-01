<?php

/**
 * @file
 * Definition of Drupal\mcapi\Plugin\views\field\Currency.
 */

namespace Drupal\mcapi\Plugin\views\field;

use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\Plugin\views\display\DisplayPluginBase;
use Drupal\views\ResultRow;
use Drupal\Core\Form\FormStateInterface;

/**
 * Field handler to show the currency of the transaction
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("curr_id")
 */
class Currency extends FieldPluginBase {


  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['machine_name'] = array('default' => FALSE, 'bool' => TRUE);

    return $options;
  }

  /**
   * Provide machine_name option for currency display.
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);

    $form['machine_name'] = array(
      '#title' => t('Output machine name'),
      '#description' => t('Display field as the currency machine name.'),
      '#type' => 'checkbox',
      '#default_value' => !empty($this->options['machine_name']),
    );
  }

  public function query($use_groupby = FALSE) {
    $table_alias = $this->ensureMyTable();
    $this->query->addField($table_alias, 'curr_id', 'mcapi_transactions_worths_currcode');
  }

  function render(ResultRow $values) {
    $value = $values->mcapi_transactions_worths_currcode;
    if ($this->options['machine_name']) {
      return $value;
    }
    else {
      return currency_load($value)->id();
    }
  }

}
