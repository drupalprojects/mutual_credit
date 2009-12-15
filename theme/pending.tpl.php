<?php 
/*
 * Themes a list of pending transactions, according to the perspective of one user
 * Receives three arguments
 * 
 * $this_user //the user object of the user whose perspective this is
 * $waiting_on_other  //array of pending transaction objects started by the user (or NULL)
 * $waiting_on_me  //array of pending transaction objects awaiting competion by the user  (or NULL)
 */ 

if (!count($waiting_on_user) && !count($waiting_on_other)) print t('There are no pending transactions');

if (count($waiting_on_user)) { ?>
<h5><?php print t('Transactions for !user to sign off', array('!user' => strip_tags(theme_username($account)))); ?></h5>
  <?php foreach ($waiting_on_user as $transaction) { 
  print theme('transaction', $transaction, TRUE);
  }
}?>

<?php if (count($waiting_on_other)) { ?>
<h5><?php print t('Transactions !user started', array('!user' => strip_tags(theme_username($account)))); ?></h5>
<?php foreach ($waiting_on_other as $transaction) { 
  print theme('transaction', $transaction, TRUE);
  }
}?>
