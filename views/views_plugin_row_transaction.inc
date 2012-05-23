<?php

class views_plugin_row_transaction extends views_plugin_row {

  // Basic properties that let the row style follow relationships.
  var $base_table = 'mcapi_transactions';
  var $base_field = 'serial';

  // Stores the nodes loaded with pre_render.
  var $transactions = array();

  function option_definition() {
    $options = parent::option_definition();

    $options['view_mode'] = array('default' => 'sentence');

    return $options;
  }

  function options_form(&$form, &$form_state) {
    parent::options_form($form, $form_state);

    $options = $this->options_form_summary_options();
    $form['view_mode'] = array(
      '#type' => 'select',
      '#options' => $options,
      '#title' => t('View mode'),
      '#default_value' => $this->options['view_mode'],
     );
  }

  /**
   * Return the main options, which are shown in the summary title.
   */
  function options_form_summary_options() {
    $entity_info = entity_get_info('transaction');
    $options = array();
    if (!empty($entity_info['view modes'])) {
      foreach ($entity_info['view modes'] as $mode => $settings) {
        $options[$mode] = $settings['label'];
      }
    }
    return $options;
  }

  function summary_title() {
    $options = $this->options_form_summary_options();
    return check_plain($options[$this->options['view_mode']]);
  }

  function pre_render($values) {
    $serials = array();
    foreach ($values as $row) {
      $serials[] = $row->{$this->field_alias};
    }
    $this->transactions = transactions_load(array(), array('serial' => $serials));
  }

  function render($row) {
    $transaction = $this->transactions[$row->{$this->field_alias}];
    $build = transaction_view($transaction, $this->options['view_mode']);
    return drupal_render($build);
  }
}
