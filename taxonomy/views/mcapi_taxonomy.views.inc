<?php

/*
 * create the relationship between taxonomy and transactions
 */
function mcapi_taxonomy_views_data() {
  $data['mcapi_transactions']['term_transaction_tid'] = array(
    'title' => t('Taxonomy terms on transaction'),
    'relationship' => array(
      'handler' => 'views_handler_relationship_transaction_term_data',
      'help' => t('Relate transactions to taxonomy terms, specifiying which vocabulary or vocabularies to use. This relationship will cause duplicated records if there are multiple terms.'),
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
  return $data;
}
