<?php
// $Id: views_handler_field_mcapi_description.inc
/**
 * @file
 * Contains the basic 'exchange' field handler.
 */

/**
 * Field handler to provide simple renderer that allows linking to a exchange.
 */
class views_handler_field_mcapi_description extends views_handler_field {

  function init(&$view, &$options) {
    parent::init($view, $options);
    if (!empty($this->options['link_to_exchange'])) {
      $this->additional_fields['xid'] = 'xid';
    }
  }

  function option_definition() {
    $options = parent::option_definition();
    $options['link_to_exchange'] = array('default' => TRUE);
    return $options;
  }

  /**
   * Provide link to exchange option
   */
  function options_form(&$form, &$form_state) {
    parent::options_form($form, $form_state);
    $form['link_to_exchange'] = array(
      '#title' => t('Link this field to its exchange'),
      '#description' => t('This will override any other link you have set.'),
      '#type' => 'checkbox',
      '#default_value' => !empty($this->options['link_to_exchange']),
    );
  }

  /**
   * Render whatever the data is as a link to the exchange.
   *
   * Data should be made XSS safe prior to calling this function.
   */
  function render_link($data, $values) {
    if (!empty($this->options['link_to_exchange'])) {
      if ($data !== NULL && $data !== '') {
        $this->options['alter']['make_link'] = TRUE;
        $this->options['alter']['path'] = "exchange/" . $values->{$this->aliases['xid']};
        if (isset($this->aliases['language'])) {
          $languages = language_list();
          if (isset($languages[$values->{$this->aliases['language']}])) {
            $this->options['alter']['language'] = $languages[$values->{$this->aliases['language']}];
          }
          else {
            unset($this->options['alter']['language']);
          }
        }
      }
      else {
        $this->options['alter']['make_link'] = FALSE;
      } 
    }
    return $data;
  }

  function render($values) {
    return $this->render_link(check_plain($values->{$this->field_alias}), $values);
  }
}
