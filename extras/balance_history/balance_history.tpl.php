<script type="text/javascript" src="http://www.google.com/jsapi"></script>
<script type="text/javascript">
  google.load('visualization', '1', {packages: ['corechart']});</script>
<?php
////$Id: balance_history.tpl.php,v 1.3 2010/12/08 11:43:18 matslats Exp $

/*
 * Balance History Google Chart
 * Takes data in the format below and outputs an <img> tag for a google chart.
 * Feel free to tweak the initial variables
 * //TODO This could be cached.
 *
 * $account = User Obj
 * $histories = array(
 *   '$currcode' = array(
 *     '$unixtime' => $balance
 *     '$unixtime' => $balance
 *     etc...
 *   )
 * );
 *
 * https://developers.google.com/chart/interactive/docs/gallery/linechart
 */

$color_sequence = array('21a0db', '2aab49');
foreach ($histories as $currcode => $history) {
  $colors[] = "'".array_pop($color_sequence) ."'";
  foreach ($history as $timestamp => $balance) {
    $timeline[$timestamp][$currcode] = $balance;
  }
}
if (empty($timeline))return '';
//$timeline is now a list of times and changes of balance in currencies
ksort($timeline);
//what we need is a list of times with both balances per moment
$prev = array();
foreach ($timeline as $timestamp => $balances) {
  $timeline[$timestamp] = $balances + $prev;
  $prev = $timeline[$timestamp];
}
$id = 'uid-'.$account->uid.'-'.implode('',array_keys($histories));?>
<script type="text/javascript">
function drawBalanceHistory() {
  var data = new google.visualization.DataTable();
  data.addColumn('date', 'Date');
<?php foreach (array_keys(current($timeline)) as $currcode) { ?>
  data.addColumn('number', '<?php print $currcode; ?>');
<?php } ?>

<?php foreach ($timeline as $timestamp => $balances) {
  $date = 'new Date("'.date('m/d/Y', $timestamp).'")';?>
  data.addRow([<?php print $date; ?>, <?php print implode(', ', $balances);?>]);
<?php } ?>
  var options = {
    curveType: "<?php print $curvetype; ?>",
    width: 250,
    height: 200,
    colors: [<?php print implode(', ', $colors);?>],
  }
  new google.visualization.LineChart(document.getElementById('<?php print $id; ?>')).draw(data, options);
}
google.setOnLoadCallback(drawBalanceHistory);
</script>
<div id="<?php print $id;?>" style="width:<?php print $width; ?>px; height:<?php print intval(3*$width/4);?> px;"></div>
