<?php
//Id;

/*
 * Implements views hook_views_data
 */
function mcapi_vis_views_data() {
  //these two fields are equivalent to their counterparts in {mcapi_cache}
  //exept they are calculated dynamically, which is particularly useful for accounting periods.
  $data['mcapi_transactions']['income'] = array(
    'title' => t('Income'), // The item it appears as on the UI,
    'help' => t('Sum of transactions where participant was payee'),
    'field' => array(
      'handler' => 'views_handler_field_mcapi_income',
      'click sortable' => TRUE,
    ),
    'sort' => array(
      'handler' => 'views_handler_sort',
    )
  );
  $data['mcapi_transactions']['expenditure'] = array(
    'title' => t('Expenditure'),
    'help' => t('Sum of transactions where participant was payer'),
    'field' => array(
      'handler' => 'views_handler_field_mcapi_expenditure',
      'click sortable' => TRUE,
    ),
    'sort' => array(
      'handler' => 'views_handler_sort',
    )
  );
  /*
  $data['mcapi_transactions']['promiscuity'] = array(
    'title' => t('Promiscuity'),
    'help' => t('Number of partners'),
    'field' => array(
      'handler' => 'views_handler_field_mcapi_promiscuity',
      'click sortable' => TRUE,
    ),
    'sort' => array(
      'handler' => 'views_handler_sort',
    )
  );
   */
  return $data;
}