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
  stdClass Object (
    [title] => gift from darren to edward
    [url] => <a href="/node/45">gift from darren to edward</a>
    [nid] => 45
    [payer_uid] => 4
    [payee_uid] => 5
    [starter_uid] => 4
    [completer_uid] => 5
    [other_user] => stdClass Object USER OBJ...
    [notme] => <a href="/user/4" title="View user profile.">darren</a>
    [cid] => 0
    [amount] => <span class="currency"><img src="/sites/marketplace.drupal6/files/currencies/gold.jpg" border ="0" />8</span>
    [quantity] => 8
    [quality] => 0
    [state] => 0
    [created] => 31/08/09
    [transaction_type] => outgoing_direct
    [balance] => -<span class="currency"><img src="/sites/marketplace.drupal6/files/currencies/gold.jpg" border ="0" />3</span>
    [expenditure] => <span class="currency"><img src="/sites/marketplace.drupal6/files/currencies/gold.jpg" border ="0" />0</span>
OR  [income] => <span class="currency"><img src="/sites/marketplace.drupal6/files/currencies/gold.jpg" border ="0" />0</span>
    [class] => 'credit' OR 'debit'
    [actions] => SOME HTML...
)
 */
 
  //need to delare the column headings, their order, and associated fields
  //array keys must correspond to the keys in the transaction objects
  $columns = array(
    'created' => t('Date'),
    'url' => t('Description'), 
    'notme' => t('With'),
    'quantity' => t('Amount'),
    'quality' => t('Rating'),
    'incoming' => t('Income'),
    'outgoing' => t('Expenditure'),
    'balance' => t('Running Total'),
    'actions' => '',
  );
  //put the given array into the columns declared to make a table
  foreach($transactions as $key => $transaction) {
    foreach ($columns as $field => $title){
      $rows[$key][$field] = $transaction->$field;
    }
  }
  if (!isset($transaction->quality))unset ($columns['quality']);
  
  print theme('table', $columns, $rows) . 
    theme('pager', NULL, 1, TRANSACTIONS_PAGER_ELEMENT);