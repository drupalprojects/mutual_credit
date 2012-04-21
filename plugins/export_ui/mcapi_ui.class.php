<?php

class mcapi_ui extends ctools_export_ui {

  function init($plugin) {
    $prefix_count = count(explode('/', $plugin['menu']['menu prefix']));
    $plugin['menu']['items']['add-template'] = array(
      'path' => 'template/%/add',
      'title' => 'Add from template',
      'page callback' => 'ctools_export_ui_switcher_page',
      'page arguments' => array($plugin['name'], 'add_template', $prefix_count + 2),
      'load arguments' => array($plugin['name']),
      'access callback' => 'ctools_export_ui_task_access',
      'access arguments' => array($plugin['name'], 'add_template', $prefix_count + 2),
      'type' => MENU_CALLBACK,
    );
    return parent::init($plugin);
  }

  /**
   * Build a row based on the item.
   *
   * By default all of the rows are placed into a table by the render
   * method, so this is building up a row suitable for theme('table').
   * This doesn't have to be true if you override both.
   *
   * but what I want, is to add the object->path, as a link, to the table
   */
  function list_build_row($item, &$form_state, $operations) {
    // Set up sorting
    $machine_name = $item->{$this->plugin['export']['key']};

    switch ($form_state['values']['order']) {
      case 'disabled':
        $this->sorts[$machine_name] = empty($item->disabled) . $machine_name;
        break;
      case 'title':
        $this->sorts[$machine_name] = $item->{$this->plugin['export']['admin_title']};
        break;
      case 'name':
        $this->sorts[$machine_name] = $machine_name;
        break;
      case 'storage':
        $this->sorts[$machine_name] = $item->type . $machine_name;
        break;
    }

    $this->rows[$machine_name]['data'] = array();
    $this->rows[$machine_name]['class'] = !empty($item->disabled) ? array('ctools-export-ui-disabled') : array('ctools-export-ui-enabled');
    //first col, Name
    $this->rows[$machine_name]['data'][] = array('data' => check_plain($item->data->name), 'class' => array('ctools-export-ui-title'));
    //second col, format
    $this->rows[$machine_name]['data'][] = array('data' => array(
      '#theme' =>'worth_field',
      '#currcode' => $item->currcode,
      '#quantity' => 99.00
    ));
    //third col, usage
    $this->rows[$machine_name]['data'][] = array('data' => db_query("SELECT COUNT(entity_id) FROM {field_data_worth} WHERE worth_currcode = '$item->currcode'")->fetchField());
    //fourth col, storage
    $this->rows[$machine_name]['data'][] = array('data' => check_plain($item->type), 'class' => array('ctools-export-ui-storage'));
    //final col, links
    $ops = theme('links__ctools_dropbutton', array('links' => $operations, 'attributes' => array('class' => array('links', 'inline'))));
    $this->rows[$machine_name]['data'][] = array('data' => $ops, 'class' => array('ctools-export-ui-operations'));
    // Add an automatic mouseover of the description if one exists.
    if (!empty($this->plugin['export']['admin_description'])) {
      $this->rows[$machine_name]['title'] = $item->{$this->plugin['export']['admin_description']};
    }
  }

  /**
   * Provide the table header.
   *
   * If you've added columns via list_build_row() but are still using a
   * table, override this method to set up the table header.
   */
  function list_table_header() {
    $header = array();
    if (!empty($this->plugin['export']['admin_title'])) {
      $header[] = array('data' => t('Title'), 'class' => array('ctools-export-ui-title'));
    }
    else{
      $header[] = array('data' => t('Currency code'), 'class' => array('ctools-export-ui-name'));
    }

    $header[] = array('data' => t('Format'), 'class' => array('ctools-export-ui-name'));
    $header[] = array('data' => t('Transactions'), 'class' => array('ctools-export-ui-storage'));
    $header[] = array('data' => t('Storage'), 'class' => array('ctools-export-ui-storage'));
    $header[] = array('data' => t('Operations'), 'class' => array('ctools-export-ui-operations'));

    return $header;
  }

  function edit_form(&$form, &$form_state) {
    ctools_include('export');
    parent::edit_form($form, $form_state);
  }
  
}
