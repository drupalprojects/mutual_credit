<?php


/**
 * @file
 * Field handler to show quantities IN THE INDEX TABLE
 * NB this doesn't apply to the worth_field
 * @ingroup views_field_handlers
 */
class views_handler_field_mcapi_index_currcode extends views_handler_field {

  function render($values) {
    return entity_load('mcapi_currencies', $values->{$this->field_alias})->human_name;
  }
}
