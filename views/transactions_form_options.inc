<?php
// $Id$

/**
 * @file
 * Handler for 'array' display.
 */
class transaction_form_options extends views_plugin_display {
  function execute() {
    return $this->view->render($this->display->id);
  }

  function render() {
    return !empty($this->view->result) || !empty($this->view->style_plugin->definition['even empty']) ? $this->view->style_plugin->render($this->view->result) : '';
  }

  function uses_exposed() {
    return FALSE;
  }
}

//this isn't needed. not sure what it does
class content_plugin_display_references extends transaction_form_options {
  function query() {
    $options = $this->get_option('content_options');

    if ($options['string'] !== '') {
      $match_clauses = array(
        'contains' => "LIKE '%%%s%%'",
        'equals' => "= '%s'",
        'starts_with' => "LIKE '%s%%'",
      );
      $clause = isset($match_clauses[$options['match']]) ? $match_clauses[$options['match']] : $match_clauses['contains'];
      $alias = $this->view->query->ensure_table($options['table']);
      $this->view->query->add_where(NULL, "$alias.$options[field_string] $clause", $options['string']);
    }
    elseif ($options['ids']) {
      $alias = $this->view->query->ensure_table($options['table']);
      $this->view->query->add_where(NULL, "$alias.$options[field_id] IN (" . db_placeholders($options['ids']) . ')', $options['ids']);
    }
  }
}

