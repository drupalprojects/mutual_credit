<?php

namespace Drupal\mcapi_limits\Plugin\views\field;

use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\ResultRow;
use Drupal\mcapi\Exchange;

/**
 * Field handler to show a user's balance limits.
 *
 * @ingroup views_field_handlers
 *
 * @PluginID("mcapi_limits")
 */
class Limits extends FieldPluginBase {

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['currencies'] = array('default' => []);
    $options['absolute'] = array('default' => 'absolute');
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, $form_state) {
    $form['currencies'] = array(
      '#title' => t('Currencies'),
      '#title_display' => '#before',
      '#description' => t('Select none to see all the currencies the user can use'),
      '#type' => 'mcapi_currency',
      '#multiple' => TRUE,
      '#default_value' => $this->options['currencies'],
    );
    $form['absolute'] = array(
      '#title' => t('Range'),
      '#type' => 'radios',
      '#options' => array(
        'absolute' => t('Show min, max and current balance'),
        'relative' => t('Show limits for earning and spending, relative to balance'),
      ),
      '#default_value' => $this->options['absolute'],
      '#weight' => '5',
    );
    parent::buildOptionsForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
    $this->addAdditionalFields();
  }

  /**
   * {@inheritdoc}
   */
  public function render(ResultRow $values) {
    $wallet = $this->getEntity($values);
    module_load_include('inc', 'mcapi_limits');
    return mcapi_view_limits($wallet);
  }

}
