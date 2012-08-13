<?php


/**
 * Argument handler for a year plus month (CCYYMM)
 */
class views_handler_argument_mcapi_date extends views_handler_argument_date {
  /**
   * Constructor implementation
   */
  function construct() {
    parent::construct();
    $this->format = 'F Y';
    $this->arg_format = 'Ym';
    $field = $this->real_field ? $this->real_field : $this->definition['fieldname'];
    $this->formula = views_date_sql_format($this->arg_format, "***table***.$field");
  }

  /**
   * Provide a link to the next level of the view
   */
  function summary_name($data) {
    $date = $data->{$this->name_alias};
    return format_date(strtotime($date . "15" . " 00:00:00 UTC"), 'custom', $this->format, 'UTC');
  }

  /**
   * Provide a link to the next level of the view
   */
  function title() {
    return format_date(strtotime($this->argument . "15" . " 00:00:00 UTC"), 'custom', $this->format, 'UTC');
  }

  function get_default_argument($raw = FALSE) {
    if (!$raw && $this->options['default_argument_type'] == 'date') {
      return date($this->arg_format, REQUEST_TIME);
    }
  }

  /**
   * The date handler provides some default argument types, which aren't argument default plugins,
   * so addapt the export mechanism.
   */
  function export_plugin($indent, $prefix, $storage, $option, $definition, $parents) {
    // Only use a special behaviour for the special argument types, else just
    // use the default behaviour.
    if ($option == 'default_argument_type') {
      $type = 'argument default';
      $option_name = 'default_argument_options';

      $plugin = $this->get_plugin($type);
      $name = $this->options[$option];
      if (in_array($name, array('date'))) {

        // Write which plugin to use.
        $output = $indent . $prefix . "['$option'] = '$name';\n";
        return $output;
      }
    }
    return parent::export_plugin($indent, $prefix, $storage, $option, $definition, $parents);
  }
  
  function default_argument_form(&$form, &$form_state) {
    parent::default_argument_form($form, $form_state);
    $form['default_argument_type']['#options'] += array('date' => t('Current date'));
  }
}