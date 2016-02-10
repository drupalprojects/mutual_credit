<?php

/**
 * @file
 * Definition of Drupal\mcapi\Plugin\views\field\Worth.
 * renders worth values on the transactionIndex table
 *
 * @todo in \Drupal\views\Plugin\views\display\DisplayPluginBase::gethandlers
 * this handler is automatically overridden to be numeric in group queries which
 * means there's currently no way to format worth values in aggregate queries
 * @see https://api.drupal.org/api/drupal/core!modules!views!src!Plugin!views!query!Sql.php/function/Sql%3A%3AgetAggregationInfo/8
 */

namespace Drupal\mcapi\Plugin\views\field;

use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\ResultRow;
use Drupal\Core\Form\FormStateInterface;
use Drupal\mcapi\Entity\Currency;

/**
 * When we look at transaction index table, we need to view one worth at a time
 * deriving the currency from a separate field
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("worth")
 */
class Worth extends FieldPluginBase {

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['format'] = ['default' => Currency::DISPLAY_NORMAL];
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
     $form['format'] = array(
      '#title' => t('Format'),
      '#decriptions' => $this->t('Not all formats support multiple cardinality.'),
      '#type' => 'radios',
      '#options' => Currency::formats(),
      '#default_value' => $this->options['format'],
    );
    parent::buildOptionsForm($form, $form_state);
  }

  /**
   * @note aggregation may cause problems with formatting
   * @todo if there is a currency filter on this view, we would want to only show that currency part of each worth value
   */
  public function render(ResultRow $values) {
    $settings = [
      'format' => $this->options['format']
    ];
    $worth_items = $this->getEntity($values)->worth;
    if (property_exists($values, 'curr_id')) {
      $val = [
        'curr_id' => $values->curr_id,
        'value' => $this->getValue($values)
      ];
      $worth_items->setValue($val);
    }
    return $worth_items->view(
      [
        'label' => 'hidden',
        'settings' => $settings
      ]
    );
  }

}
