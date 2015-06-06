<?php

/**
 * @file
 * Definition of Drupal\mcapi\Plugin\views\field\Worth.
 */

namespace Drupal\mcapi\Plugin\views\field;

use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\ResultRow;
use Drupal\Core\Form\FormStateInterface;

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
    $options['format'] = array('default' => 'normal');
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
      '#options' => array(
     	  'normal' => t('Normal'),
        'decimal' => t('In base 10'),
        'raw' => t('raw integer from the db')
      ),
      '#default_value' => !empty($this->options['format']),
    );
    parent::buildOptionsForm($form, $form_state);
  }

  /**
   * @note aggregation may cause problems with formatting
   */
  public function render(ResultRow $values) {
    return $this->getEntity($values)->worth->view($this->options['format']);
  }

}