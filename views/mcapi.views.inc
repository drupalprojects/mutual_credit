<?php
//$Id:
/**
 * @file
 * Contains default views for the mcapi module.
 */

/*
 * implements views hook_views_data
 */
function mcapi_views_data() {
  $data = array();
  $data['mcapi_transactions']['table']['group']  = t('Transaction');
  $data['mcapi_transactions']['table']['base'] = array(
    'database' => variable_get('mcapi_db_read', 'default'),
    'field' => 'xid',
    'title' => t('Transactions'),
    'help' => t('Records of transactions between users'),
//    'access query tag' => 'transaction_access',
    'defaults' => array(
      'field' => 'description',
    ),
  );

  $data['mcapi_transactions']['description'] = array(
    'title' => t('Description'), // The item it appears as on the UI,
    'field' => array(
      'help' => t('The substance of the transaction.'),
      'handler' => 'views_handler_field_mcapi_description',
      'click sortable' => TRUE,
    ),
    'sort' => array(
      'handler' => 'views_handler_sort',
    ),
    'filter' => array(
      'handler' => 'views_handler_filter_string',
    ),
    'argument' => array(
      'handler' => 'views_handler_argument_string',
    ),
  );
  $data['mcapi_transactions']['xid'] = array(
    'title' => t('Transaction ID'), // The item it appears as on the UI,
    'field' => array(
      'help' => t('The unique ID of the transaction.'),
      'handler' => 'views_handler_field',
    ),
    'sort' => array(
      'handler' => 'views_handler_sort',
    ),
    'argument' => array(
      'handler' => 'views_handler_argument',
    )
  );
  $data['mcapi_transactions']['payer'] = array(
    'title' => t('Payer'), // The item it appears as on the UI,
    'help' => t('The user who gave currency in the transaction.'),
    'field' => array(
      'handler' => 'views_handler_field_mcapi_trader',
      'click sortable' => TRUE,
    ),
    'filter' => array(
      'help' => t('Show only transactions where the specified user paid'),
      'handler' => 'views_handler_filter_user_name'
    ),
    'argument' => array(
      'handler' => 'views_handler_argument'
    )
  );
  $data['mcapi_transactions']['payee'] = array(
    'title' => t('Payee'),
    'help' => t('The user who received currency in the transaction.'),
    'field' => array(
      'handler' => 'views_handler_field_mcapi_trader',
      'click sortable' => TRUE,
    ),
    'filter' => array(
      'help' => t('Show only transactions where the specified user was paid'),
      'handler' => 'views_handler_filter_user_name'
    ),
    'argument' => array(
      'handler' => 'views_handler_argument'
    ),
  );
  $data['mcapi_transactions']['quantity'] = array(
    'title' => t('Amount'), // The item it appears as on the UI,
    'help' => t('Number of units of currency transferred'),
    'field' => array(
      'handler' => 'views_handler_field_mcapi_amount',
      'click sortable' => TRUE,
    ),
    'filter' => array(
      'help' => t('transactions above or below a certain value'),
      'handler' => 'views_handler_filter_numeric'
    ),
    'sort' => array(
      'handler' => 'views_handler_sort',
    )
  );
  $data['mcapi_transactions']['state'] = array(
    'title' => t('State'),
    'help' => t('Whether the transaction is completed (FALSE) or pending (TRUE)'),
    'field' => array(
      'handler' => 'views_handler_field_mcapi_state',
      'click sortable' => TRUE,
    ),
    'filter' => array(
      'handler' => 'views_handler_filter_mcapi_state',
    ),
  );
  $data['mcapi_transactions']['cid'] = array(
    'title' => t('Currency ID'),
    'help' => t('The currency the transaction was denominated in'),
    'field' => array(
      'help' => t("Use this field (check 'exclude') for the theming to work on rating and all quantities in the row"),
      'handler' => 'views_handler_field',
      'click sortable' => TRUE,
    ),
    'filter' => array(
      'handler' => 'views_handler_filter_mcapi_currency',
    ),
    'argument' => array(
      'handler' => 'views_handler_argument_numeric',
    ),
  );

  $data['mcapi_transactions']['type'] = array(
    'title' => t('Type'),
    'help' => t('Which form or module which created the transaction'),
    'field' => array(
      'handler' => 'views_handler_field',
      'click sortable' => TRUE
    ),
    'filter' => array(
      'handler' => 'views_handler_filter_mcapi_types',
    ),
  );
  $data['mcapi_transactions']['creator'] = array(
    'title' => t('Creator'),
    'help' => t('The user who first created the transaction.'),
    'field' => array(
      'handler' => 'views_handler_field_mcapi_trader',
      'click sortable' => TRUE,
    ),
    'filter' => array(
      'handler' => 'views_handler_filter_user_name'
    ),
    'argument' => array(
      'handler' => 'views_handler_argument'
    ),
  );
  $data['mcapi_transactions']['created'] = array(
    'title' => t('created'),
    'help' => t("the second the transaction was created"),
    'field' => array(
      'handler' => 'views_handler_field_date',
      'click sortable' => TRUE,
    ),
    'sort' => array(
      'handler' => 'views_handler_sort_date',
    ),
    'filter' => array(
      'handler' => 'views_handler_filter_date',
    ),
    'argument' => array(
      'field' => 'created',
      'handler' => 'views_handler_argument_mcapi_created_year_month',
    )
  );
  $data['mcapi_transactions']['modifer'] = array(
    'title' => t('Modifier'),
    'help' => t('The user who last touched the transaction.'),
    'field' => array(
      'handler' => 'views_handler_field_mcapi_trader',
      'click sortable' => TRUE,
    ),
    'filter' => array(
      'handler' => 'views_handler_filter_user_name'
    ),
    'argument' => array(
      'handler' => 'views_handler_argument'
    ),
  );
  $data['mcapi_transactions']['modified'] = array(
    'title' => t('Corrected'),
    'help' => t("the second the transaction was corrected"),
    'field' => array(
      'handler' => 'views_handler_field_time_interval',
      'click sortable' => TRUE,
    ),
    'sort' => array(
      'handler' => 'views_handler_sort_date',
    ),
    'filter' => array(
      'handler' => 'views_handler_filter_date',
    ),
  );
  $data['mcapi_transactions']['links'] = array(
    'title' => t('Admin links'),
    'help' => t("Edit and delete links"),
    'field' => array(
      'handler' => 'views_handler_field_mcapi_links',
    )
  );

  return $data;
}

/*
 * implements views hook_views_data_alter
 */
function mcapi_views_data_alter(&$items) {
  //not sure if this does anything
  $items['users']['table']['join']['mcapi_transactions'] = array(
    'left_table' => 'mcapi_transactions',
    'left_field' => 'creator',
    'field' => 'uid',
  );
}




//shorten the user select text boxes on the exposed views form
//this could also be done by css but I'm not creating a file just for that
function mcapi_form_views_exposed_form_alter(&$form, $form_state) {
  $form['payee']['#size'] = 30;
  $form['payer']['#size'] = 30;
}


/**
 * Field handler to provide simple renderer that allows linking to a user.
 */
class views_handler_field_transactions extends views_handler_field {

  function option_definition() {
    $options = parent::option_definition();
    $options['link_to_transaction'] = array('default' => TRUE);
    return $options;
  }

  /**
   * Provide link to transaction option
   */
  function options_form(&$form, &$form_state) {
    parent::options_form($form, $form_state);
    $form['link_to_transaction'] = array(
      '#title' => t('Link this field to its transaction'),
      '#description' => t('This will override any other link you have set.'),
      '#type' => 'checkbox',
      '#default_value' => $this->options['link_to_transaction'],
    );
  }
  function query(){
    parent::query();
    if (!empty($this->options['link_to_transaction'])) {
      $this->add_additional_fields(array('xid'));
    }
  }

  function render_link($data, $values) {
    if (!empty($this->options['link_to_transaction']) && user_access('access user profiles') && $values->{$this->aliases['xid']} && $data !== NULL && $data !== '') {
      $this->options['alter']['make_link'] = TRUE;
      $this->options['alter']['path'] = "transaction/" . $values->{$this->aliases['xid']};
    }
    return $data;
  }

  function render($values) {debug('views_handler_field_transactions');
    return $this->render_link(check_plain($values->{$this->field_alias}), $values);
  }
}
