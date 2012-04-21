<?php
//Id :

class views_handler_field_mcapi_min extends views_handler_field {
  
  function pre_render($values) {
    $uid_key = array_search('uid', array_keys($this->view->argument));
    $currcode_key = array_search('currcode', array_keys($this->view->argument));
    if ($uid_key !== FALSE && $currcode_key !== FALSE) {
      $uid = $this->view->args[$uid_key];
      $currcode = $this->view->args[$currcode_key];
      $limits = limits_get($currcode, $uid);
      $values[0]->min = $limits['min'];
    }
    else {
      return t('Argument missing');
    }
  }

  function query() {
    parent::query();
    $this->ensure_my_table();
    $this->add_additional_fields(array('currcode'));
    $this->add_additional_fields(array('uid'));
  }

  function render($values) {
    //this would be better if it happened inside a views alter hook, because we might not want to render this field at all
    //rather, we want to include it in $values, so that it can be accessed by fields e.g. with the googleometer chart plugin
    return mcapi_field_format_money($values->max, $values->mcapi_cache_currcode);
  }
}
