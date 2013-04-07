<?php

/* two different displays according to the currency type
 * $currcode is a machine name of a currency
 * $totals is an object with the following properties
 *   'balance' => float
 *   'gross_in' => float
 *   'gross_out' => float
 *   'volume' => float
 *   'count' => integer
 */

if (currency_load($currcode)->issuance == 'acknowledgement') {//A bar chart comparing given to gotten.
  $id = "given-gotten-".$currcode;?>
<script type="text/javascript" src="http://www.google.com/jsapi"></script>
<script type="text/javascript">
  google.load('visualization', '1', {packages: ['corechart']});
  google.setOnLoadCallback(drawGivenGotten);
</script>
<script type="text/javascript">
function drawGivenGotten() {
  var data = google.visualization.arrayToDataTable([
    ['',  'EXCHANGED'],
    ['Given',  <?php print $totals->gross_in;?>],
    ['Gotten',  <?php print $totals->gross_out;?>],
  ]);
  var options = {
    title:"Active hours",
    width:200,
    height:100,
    hAxis: {viewWindowMode: 'explicit'},
    hAxis: {viewWindow: {max: <?php print 5 * ceil(max($totals->gross_in, $totals->gross_out) / 5); ?>}},
    hAxis: {viewWindow: {min: 0}},
    enableInteractivity: false
  };
  new google.visualization.BarChart(document.getElementById('<?php print $id; ?>')).draw(data, options);
}</script>
<div id="<?php print $id; ?>" style="width:200px; height:100px;"></div>

<?php return; } else { //for exchange and commodity currencies, something a little more numeric
  $balance = theme('worth_item', array('currcode' => $currcode, 'quantity' => $totals->balance));
  $income = t('Income: !quant', array('!quant' => theme('worth_item', array('currcode' => $currcode, 'quantity' => $totals->gross_in))));
  $volume = t('Volume: !volume', array('!volume' => theme('worth_item', array('currcode' => $currcode, 'quantity' => $totals->volume))));
  $count = t('Transactions: @count', array('@count' => $totals->count)); ?>

  <div class = "transaction-totals <?php print $currcode;?>">
    <div class="balance"><?php print $balance; ?></div>
    <div class="gross-in"><?php print $income; ?></div>
    <div class="volume"><?php print $volume; ?></div>
    <div class="count"><?php print $count; ?></div>
  </div>
<?php } ?>