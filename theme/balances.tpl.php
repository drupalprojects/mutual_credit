<?php 
// $Id$
/* themes the balances for a given user.
 * $balances is an array of the form:
 * Array (
        [$cid] => Array (
                [cleared_balance] => REAL_NUMBER
                [pending_difference] => REAL_NUMBER
                [pending_balance] => REAL_NUMBER
                [gross_income] => REAL_NUMBER
                [quality_mean] => REAL_NUMBER
            )
    )
 * 
  Where ratings are options.
 */

  //get the original array to determine if ratings are being used
  $rating = variable_get('cc_transaction_qualities', NULL);
//print '<h4>'. t('Balances') .'</h4>';
?>
<table class = "user-balances"><thead>
  <tr>
    <td></td>
    <?php 
    foreach (array_keys($balances) as $cid){
      $curr = currency_load($cid);
      print '<th>'. $curr->title. '</th>';
    }?>
  </tr>
  </thead><tbody>
  <?php if (variable_get('count_pending', FALSE)) { ?>
  <tr>
    <th><?php print t('Balance'); ?></th>
    <?php foreach ($balances as $cid=>$bals) { 
      print "<td>" . $bals['cleared_balance'] . '</td>';
    }?>
  </tr>
  <?php } else { ?>
  <tr>
    <th><?php print t('Pending Balance'); ?></th>
     <?php foreach ($balances as $cid=>$bals) {
        print "<td>" .$bals['pending_balance'] . '</td>';
      }?>
  </tr>
  <?php } ?>
  <tr>
    <th><?php print t('Unconfirmed total'); ?></th>
     <?php foreach ($balances as $cid=>$bals) {
        print "<td>" .$bals['pending_difference'] . '</td>';
      }?>
  </tr>
  <tr>
    <th> <?php print t('Gross income') ?> </th>
    <?php foreach ($balances as $cid=>$bals) {
      print "<td>" .$bals['gross_income'] . '</td>';
    }?>
  </tr>
  <tr>
    <th> <?php print t('Gross expenditure') ?> </th>
    <?php foreach ($balances as $cid=>$bals) {
      print "<td>" .$bals['gross_expenditure']. '</td>';
    }?>
  </tr>
  <?php if ($rating) { ?>
  <tr>
    <th><?php print t('Average rating'); ?> </th>
    <?php foreach ($balances as $cid=>$bals) {
       print "<td>" . $bals['quality_mean'] . '</td>';
      }
    }?>
  </tr>
</tbody></table>