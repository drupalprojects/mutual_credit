<?php

namespace Drupal\mcapi\Plugin\views\field;

use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\ResultRow;
use Drupal\Core\Form\FormStateInterface;
use Drupal\mcapi\Entity\Currency;
use Drupal\mcapi\Entity\CurrencyInterface;

/**
 * When we look at transaction index table, we need to view one worth at a time.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("worth")
 *
 * @note for aggregated worths, see src/Plugin/views/query/Sql::getAggregationInfo()
 */
class Worth extends FieldPluginBase {

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['format'] = ['default' => CurrencyInterface::DISPLAY_NORMAL];
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
      '#options' => ['blank' => t('Normal, with 0 as empty field')] + Currency::formats(),
      '#default_value' => $this->options['format'],
    );
    parent::buildOptionsForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   *
   * @note aggregation may cause problems with formatting.
   *
   * @todo if there is a currency filter on this view, we would want to only show that currency part of each worth value
   */
  public function render(ResultRow $values) {
    if ($curr_id = $this->getValue($values, 'curr_id')) {
      $value = $this->getValue($values);
      if ($value == 0 && $this->options['format'] == 'blank') {
        return '';
      }
      return Currency::load($curr_id)
        ->format($this->getValue($values), $this->options['format'])->toString();
    }
    // Otherwise there was no result - nothing to format
  }

}
