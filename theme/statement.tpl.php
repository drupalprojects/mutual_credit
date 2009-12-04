<?php
  if (!count($transactions)) {
    return "<p>".t('There are no completed transactions.')."</p>\n";
  }

/* statement view
 * 
 * Presently we are only preprocessing transactions like this for the 'statement'
 * values $class and $notme are preprocessing is preprocessing is specific to who's statement it is.
 * From the preprocessor, isi sent a $transactions array, where each transaction looks like
 * 
array (
  [title] => gift from carl to darren
  [nid] => 44
  [payer_uid] => 3
  [payee_uid] => 4
  [starter_uid] => 3
  [completer_uid] => 4
  [cid] => 0
  [quantity] => -5
  [quality] => 2
  [state] => 0
  [created] => 1251757109
  [submitted] => Mon, 08/31/2009 - 22:18
  [transaction_type] => outgoing_direct
  [balance] => -5
  [expenditure] => theme(money $quantity...)
OR[income] => theme(money $quantity...)
  [class] => "debit quality2"
  [description] => gift from carl to darren
  [starter] => <a href="/user/3" title="View user profile.">carl</a>
  [completer] => <a href="/user/4" title="View user profile.">darren</a>
  [amount] => theme(money $quantity...)
  [payee] => <a href="/user/3" title="View user profile.">carl</a>
  [payer] => <a href="/user/4" title="View user profile.">darren</a>
  [notme] => <a href="/user/4" title="View user profile.">darren</a>
  [transaction_link] => <a href="/node/44">gift from carl to darren</a>
  [actions] => some HTML buttons
)
 */
 
  //need to delare the column headings, their order, and associated fields
  //array keys must correspond to the keys in the transaction objects
  $columns = array(
    'submitted' => t('Date'),
    'transaction_link' => t('Item or service'), 
    'notme' => t('With'),
    'amount' => t('Amount'),
    'rating' => t('Feedback'),
    'income' => t('Income'),
    'expenditure' => t('Expenditure'),
    'balance' => t('Running Total'),
    'actions' => '',
  );
  if (!variable_get('cc_transaction_qualities', array())) unset ($columns['quality']);
  //put the given array into the columns declared to make a table
  foreach($transactions as $key => $transaction) {
    foreach ($columns as $field => $title){
      $rows[$key]['data'][$field] = $transaction[$field];
      $rows[$key]['class'] = $class;
    }
  }
  if (!isset($transaction->quality))unset ($columns['quality']);
  
  print theme('table', $columns, $rows) . 
    theme('pager', NULL, 1, TRANSACTIONS_PAGER_ELEMENT);