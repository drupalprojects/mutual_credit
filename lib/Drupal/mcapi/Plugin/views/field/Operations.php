<?php

/**
 * @file
 * Definition of Drupal\mcapi\Plugin\views\field\Operations
 */

namespace Drupal\mcapi\Plugin\views\field;

use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\Component\Annotation\PluginID;
use Drupal\views\ResultRow;

/**
 * Field handler to present a link to the node.
 *
 * @ingroup views_field_handlers
 *
 * @PluginID("transaction_operations")
 */
class Operations extends FieldPluginBase {

  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['separator'] = array('default' => '');
    return $options;
  }

  /**
   * Provide link to taxonomy option
   */
  public function buildOptionsForm(&$form, &$form_state) {
     $form['separator'] = array(
      '#title' => t('Separator between different currency quantities'),
      '#type' => 'textfield',
      '#size' => 10,
      '#default_value' => !empty($this->options['separator']),
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
  	//need to work in the options[separater] somehow
    return transaction_get_links($this->getEntity($values), '', TRUE);
  }

}
