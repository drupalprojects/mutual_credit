<?php
// $Id: views_handler_field_mcapi_links.inc,v 1.1.2.2 2010/12/22 20:21:51 matslats Exp $

/**
 * @file
 * Field handler to allow linking to a payer's user page
 * @ingroup views_field_handlers
 */
class views_handler_field_mcapi_links extends views_handler_field {

  function options(&$options) {
    parent::options($options);
    $options['include_view'] = FALSE;
  }

  function options_form(&$form, &$form_state) {
    parent::options_form($form, $form_state);
    if ($this->definition['group'] == t('Exchanges')) {
      $form['include_view'] = array(
        '#type' => 'checkbox',
        '#title' => t("Include 'view' link, along with edit (and delete)."),
        '#default_value' => $this->options['include_view'],
      );
    }
  }

  function query() {
    //this is needed because the address of this function implies a db field which doesn't exist
    //see mc_display.views.inc $data['mc_exchanges']['participant']
    $this->ensure_my_table();
    $this->add_additional_fields(array('xid'));
  }

  function render($values) {
    $transaction = transaction_load($values->xid);
    $links = array();
    if ($this->options['include_view']) {
      if (transaction_access('view', $transaction)) {
        $links[] = array(
          'title' => t('View'),
          'href' => 'transaction/'.$values->xid
        );
      }
    }
    if (module_exists('mcapi_webforms')) {
      if (transaction_access('update', $transaction)) {
        $links[] = array(
          'title' => t('Edit'),
          'href' => 'transaction/'.$values->xid.'/formedit'
        );
      }
    }
    if (transaction_access('erase', $transaction)) {
      $links[] = array(
        'title' => t('Delete'), 
        'href' => 'transaction/'.$values->xid.'/delete'
      );
    }
    if (count($links)) return theme('links', array('links' => $links));
  }
}
