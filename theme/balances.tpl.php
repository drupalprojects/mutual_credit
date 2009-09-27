<?php 
/* themes the balances for a given user.
 * $balances is an array of the form:
 * Array (
        [$cid] => Array (
                [balance] => REAL_NUMBER
                [pending_difference] => REAL_NUMBER
                [pending_balance] => REAL_NUMBER
                [gross_income] => REAL_NUMBER
                [quality_mean] => REAL_NUMBER
            )
        [$cid] => Array (
                [balance] => REAL_NUMBER
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
  <tr>
    <th><?php print t('Balance'); ?></th>
    <?php foreach ($balances as $cid=>$bals) { 
      print "<td>" . $bals['balance'] . '</td>';
    }?>
  </tr>
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
    <th><?php print t('Rating'); ?> </th>
    <?php foreach ($balances as $cid=>$bals) {
       print "<td>" . $bals['quality_mean'] . '</td>';
      }
    }?>
  </tr>
</tbody></table>