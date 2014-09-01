<?php

/**
 * @file
 * Contains \Drupal\mcapi\Element\Types.
 */

namespace Drupal\Core\Render\Element;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;

/**
 * Provides a form element for selecting a transaction state
 *
 * @FormElement("mcapi_types")
 */
class Types extends Radios {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    $class = get_class($this);
    return array(
      '#input' => TRUE,
      '#title_display' => 'before',
      '#process' => array(
        array($class, 'mcapi_process_types'),
        'ajax_process_form'
      ),
      '#theme_wrappers' => array('form_element'),
      '#theme' => 'select'
    );
  }

  /**
   * process callback for mcapi_types form element
   *
   * @return array
   *   the processed $element
   */
  function mcapi_process_types($element) {
    $element['#options'] = mcapi_entity_label_list('mcapi_type');
    return $element;
  }
}