<script type="text/javascript" src="http://www.google.com/jsapi"></script>
<script type="text/javascript">google.load('visualization', '1', {packages: ['corechart']});</script>
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
  $colors[] = "'".next($color_sequence) ."'";
  foreach ($history as $timestamp => $balance) {
    $timeline[$timestamp][$currcode] = $balance;
  }
}
ksort($timeline);
$prev = array();
foreach ($timeline as $timestamp => $balances) {
  $timeline[$timestamp] = $balances + $prev;
  $prev = $timeline[$timestamp];
}
$cache_id = 'uid'.$account->uid.implode('',array_keys($histories));?>
<script type="text/javascript">
function drawbalancehistory() {
  var data = new google.visualization.DataTable();
  data.addColumn('date', 'Date');
<?php foreach (array_keys(current($timeline)) as $currcode) { ?>
  data.addColumn('number', '<?php print $currcode; ?>');
<?php } ?>

<?php foreach ($timeline as $timestamp => $balances) {
  $date = 'new Date("'.date('m/d/Y', $timestamp).'")';?>
  data.addRow([<?php print $date; ?>, <?php print implode(', ', $balances);?>]);
<?php } ?>
  new google.visualization.LineChart(document.getElementById('<?php print $cache_id;?>')).draw(data, {
      curveType: "<?php print $curvetype; ?>",
      width: 250,
      height: 200,
      colors: [<?php print implode(', ', $colors);?>],
  });
}
google.setOnLoadCallback(drawbalancehistory);
</script>
<div id="<?php print $cache_id;?>" style="width: 250px; height: 200px;"></div>
