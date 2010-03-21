<?php
  if (!count($transactions)) {
    return "<p>".t('There are no completed transactions.')."</p>\n";
  }
  $transactions_per_page = 10;
  
/* statement view
 * 
 * Presently we are only preprocessing transactions like this for the 'statement'
 * VARIABLES:
 * $transactions array, in ASCending order of node creation: 
 *   Each transaction looks like
 * 
object (
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
  [class] => "debit quality2" //so you can theme according to the transaction direction and rating
  [description] => gift from carl to darren
  [starter] => <a href="/user/3" title="View user profile.">carl</a>
  [completer] => <a href="/user/4" title="View user profile.">darren</a>
  [amount] => theme(money $quantity...)
  [payee] => <a href="/user/3" title="View user profile.">carl</a>
  [payer] => <a href="/user/4" title="View user profile.">darren</a>
  [notme] => <a href="/user/4" title="View user profile.">darren</a>
  [transaction_link] => <a href="/node/44">gift from carl to darren</a>
  [actions] => some HTML links
)
 */
 $transactions = array_reverse($transactions);
 
  //do the stuff with the pager
  if ($transactions_per_page) {
    global $pager_total, $pager_page_array;
    $pager_total[TRANSACTIONS_PAGER_ELEMENT] = count($transactions)/$transactions_per_page +1;
    $page = isset($_GET['page']) ? $_GET['page'] : '';
    $pager_page_array = explode(',', $page); 
    $page_no = $pager_page_array[TRANSACTIONS_PAGER_ELEMENT];
    $first_result = $page_no*$transactions_per_page;
    $transactions = array_slice($transactions, $page_no*$transactions_per_page, $transactions_per_page);
  }
  
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
  );
  if (strlen($actions)) $columns['actions'] = $actions;
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
    theme('pager', NULL, $transactions_per_page, TRANSACTIONS_PAGER_ELEMENT);