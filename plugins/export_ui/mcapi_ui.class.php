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
    $currcode = $item->{$this->plugin['export']['key']};
    unset($operations['disable']);
    if ($currcode == 'def_drup') {
      unset($operations['delete']);
    }

    switch ($form_state['values']['order']) {
      case 'disabled':
        $this->sorts[$currcode] = empty($item->disabled) . $currcode;
        break;
      case 'title':
        $this->sorts[$currcode] = $item->{$this->plugin['export']['admin_title']};
        break;
      case 'name':
        $this->sorts[$currcode] = $currcode;
        break;
      case 'storage':
        $this->sorts[$currcode] = $item->type . $currcode;
        break;
    }
    $this->rows[$currcode]['data'] = array();
    $this->rows[$currcode]['class'] = !empty($item->disabled) ? array('ctools-export-ui-disabled') : array('ctools-export-ui-enabled');
    //first col, Name
    $this->rows[$currcode]['data'][1] = array('data' => check_plain($item->data->human_name), 'class' => array('ctools-export-ui-title'));
    //second col, usage
    $this->rows[$currcode]['data'][2] = array('data' => db_query("SELECT COUNT(entity_id) FROM {field_data_worth} WHERE worth_currcode = '$item->currcode'")->fetchField());
    //third col, format
    $this->rows[$currcode]['data'][3] = array('data' => theme('worth_item', array(
      'currcode' => $currcode,
      'quantity' => -99.00
    )));
    //fourth col, storage
    $this->rows[$currcode]['data'][4] = array('data' => check_plain($item->type), 'class' => array('ctools-export-ui-storage'));
    //final col, links
    $ops = theme('links__ctools_dropbutton', array('links' => $operations, 'attributes' => array('class' => array('links', 'inline'))));
    $this->rows[$currcode]['data'][5] = array('data' => $ops, 'class' => array('ctools-export-ui-operations'));
    // Add an automatic mouseover of the description if one exists.
    if (!empty($this->plugin['export']['admin_description'])) {
      $this->rows[$currcode]['title'] = $item->{$this->plugin['export']['admin_description']};
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

    $header[] = array('data' => t('Transactions'), 'class' => array('ctools-export-ui-storage'));
    $header[] = array('data' => t('Display format'), 'class' => array('ctools-export-ui-name'));
    $header[] = array('data' => t('Storage'), 'class' => array('ctools-export-ui-storage'));
    $header[] = array('data' => t('Operations'), 'class' => array('ctools-export-ui-operations'));

    return $header;
  }

  function list_page($js, $input) {
    $this->items = ctools_export_crud_load_all($this->plugin['schema'], $js);
        // This is where the form will put the output.
    $this->rows = array();
    $this->sorts = array();

    $form_state = array(
      'plugin' => $this->plugin,
      'input' => $input,
      'rerender' => TRUE,
      'no_redirect' => TRUE,
      'object' => &$this,
    );

    if (!isset($form_state['input']['form_id'])) {
      $form_state['input']['form_id'] = 'ctools_export_ui_list_form';
    }
    //this populates $this->rows
    $form = drupal_build_form('ctools_export_ui_list_form', $form_state);

    return $this->list_render($form_state);
  }

}
