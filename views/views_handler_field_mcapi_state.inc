<?php
//Id;

class views_handler_field_mcapi_state extends views_handler_field {

  private $states;

  function __construct() {
    $this->states = mcapi_get_states('#options');
  }

  function render($values) {
    return $this->states[$this->get_value($values)];
  }
}