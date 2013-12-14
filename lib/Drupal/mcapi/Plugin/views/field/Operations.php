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
    $options['mode'] = array('default' => 'page');
    return $options;
  }

  /**
   * Provide link to taxonomy option
   */
  public function buildOptionsForm(&$form, &$form_state) {
     $form['mode'] = array(
      '#title' => t('Link mode'),
      '#type' => 'radios',
      '#options' => array(
     	  'page' => t('New page'),
        'modal' => t('Modal window'),
        'ajax' => t('In-place (AJAX)')
      ),
      '#default_value' => !empty($this->options['mode']),
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
  	$transaction = $this->getEntity($values);
  	module_load_include('inc', 'mcapi');
    return transaction_get_links($transaction, $this->options['mode'], TRUE);
  }

}
