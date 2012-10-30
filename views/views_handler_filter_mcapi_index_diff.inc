<?php
/**
 * Simple filter to handle matching of boolean values.
 *
 * This handler checks to see if a string field is empty (equal to '') or not.
 * It is otherwise identical to the parent operator.
 *
 *
 */
class views_handler_filter_mcapi_index_direction extends views_handler_filter_numeric {

/*
  function get_value_options() {
    $this->value_options = array('incoming' => t('Incoming'), 'outgoing' => t('Outgoing'));
  }

  function option_definition() {
    $options = parent::option_definition();
    $options['value']['default'] = 'outgoing';
    return $options;
  }

  function expose_options() {
    parent::expose_options();
    $this->options['expose']['operator_id'] = '';
    $this->options['expose']['label'] = t('Direction');
  }
*/
  function operators() {
    $operators = array(
      '=' => array(
        'title' => t('None'),
        'short' => t('none'),
        'values' => 0,
        'ensure_my_table' => TRUE
      ),
      '>' => array(
        'title' => t('Incoming'),
        'short' => t('incoming'),
        'values' => 0,
        'ensure_my_table' => TRUE
      ),
      '<' => array(
        'title' => t('Outgoing'),
        'short' => t('outgoing'),
        'values' => 0,
        'ensure_my_table' => TRUE
      )
    );
    return $operators;
  }


  function query() {
    if ($this->operator == '=') return;
    $this->ensure_my_table();
    $this->query->add_where($this->options['group'], "$this->table_alias.$this->real_field", 0, $this->operator);
  }

  function exposed_form(&$form, &$form_state) {
    parent::exposed_form($form, $form_state);
    $form[$this->real_field.'_op']['#type'] = 'radios';
    unset($form[$this->real_field]);
  }
  function _options_form(&$form, &$form_state) {
    //$this->show_expose_button($form, $form_state);

    $form['clear_markup_start'] = array(
      '#markup' => '<div class="clearfix">',
    );

    // Add the subform from operator_form().
    $this->show_operator_form($form, $form_state);

    // Add the subform from value_form().
    $this->show_value_form($form, $form_state);

    $form['clear_markup_end'] = array(
      '#markup' => '</div>',
    );
    // Add the subform from expose_form().
    $this->show_expose_form($form, $form_state);
  }


}