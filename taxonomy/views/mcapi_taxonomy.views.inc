<?php

/*
 * create the relationship between taxonomy and transactions
 */
function mcapi_taxonomy_views_data() {
  $data['taxonomy_index_transaction']['table']['join'] = array(
    'taxonomy_term_data' => array(
      'field' => 'tid',
      'left_field' => 'tid',
    )
  );

  $data['mcapi_transactions']['term_transaction_tid'] = array(
  //$data['taxonomy_index_transaction']['tid'] = array(
    'title' => t('Taxonomy terms on transaction'),
    'relationship' => array(
      'help' => t('Relate transactions to taxonomy terms, specifiying which vocabulary or vocabularies to use. This relationship will cause duplicated records if there are multiple terms.'),
      'handler' => 'views_handler_relationship_transaction_term_data',
      'label' => t('term'),
      'base' => 'taxonomy_term_data',
    ),
    'field' => array(
      'title' => t('All taxonomy terms'),
      'help' => t('Display all taxonomy terms associated with a transaction from specified vocabularies.'),
      'handler' => 'views_handler_field_term_transaction_tid',
      'no group by' => TRUE,
    ),
  );
  //$data['mcapi_index']['term_transaction_tid'] = $data['mcapi_transactions']['term_transaction_tid'];
  return $data;
}

function mcapi_taxonomy_views_data_alter(&$items) {

  // The term data table
  $items['taxonomy_term_data']['table']['join'] = array(
    // This is provided for many_to_one argument
    'taxonomy_index_transaction' => array(
      'field' => 'tid',
      'left_field' => 'tid',
    ),
  );

}



/**
 * Implements hook_field_views_data().
 *
 * Views integration for taxonomy_term_reference fields. Adds a term relationship to the default
 * field data.
 *
 * @see field_views_field_default_views_data()
 */
function mcapi_taxonomy_field_views_data($field) {debug('mcapi_taxonomy_field_views_data - when does this fire');
  $data = field_views_field_default_views_data($field);
  foreach ($data as $table_name => $table_data) {
    foreach ($table_data as $field_name => $field_data) {
      debug($table_name);
      if (!in_array($field_name, array('table', 'entity_id'))) {
        $data[$table_name][$field_name]['relationship'] = array(
          'handler' => 'views_handler_relationship',
          'base' => 'taxonomy_term_data',
          'base field' => 'tid',
          'label' => t('term from !field_name', array('!field_name' => $field['field_name'])),
        );
        debug($data[$table_name]);
      }
      if (isset($field_data['filter'])) {
        $data[$table_name][$field_name]['filter']['handler'] = 'views_handler_filter_term_transaction_tid';
        $data[$table_name][$field_name]['filter']['vocabulary'] = $field['settings']['allowed_values'][0]['vocabulary'];
        debug($data[$table_name]);
      }
    }
  }
  return $data;
}