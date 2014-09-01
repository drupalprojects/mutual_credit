<?php

/**
 * @file
 * Contains \Drupal\mcapi\Element\States.
 */

namespace Drupal\Core\Render\Element;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;

/**
 * Provides a form element for selecting a transaction state
 *
 * @FormElement("mcapi_states")
 */
class States extends Radios {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    $class = get_class($this);
    return array(
      '#title_display' => 'before',
      '#process' => array(
        array($class, 'mcapi_process_states'),
        'ajax_process_form'
      ),
      '#theme_wrappers' => array('radios'),
      '#pre_render' => array('form_pre_render_conditional_form_element')
    );
  }

  public static function mcapi_process_states($element) {
    $element['#options'] = mcapi_entity_label_list('mcapi_state');
    $element = form_process_radios($element);
    //sort the currencies by weight.
    return $element;
  }
}