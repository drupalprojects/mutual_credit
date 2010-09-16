<?php 
// $Id$
$limits[$node->nid] = array('min' => $node->min, 'max' => $node->max);
if (!$node->sub) $division = t('integer');
elseif (!isset($node->data['divisions']) || !count($node->data['divisions'])) $division = t('centiles');
else {
   $vals = $node->data['divisions'];
   $divisions = array();
   foreach ($vals as $val) {
     $divisions[] = trim(strrchr($val, '|'));
     $division  = str_replace('|', '', implode(', ', $divisions));
   }
}

?>
<div class="node node-currency">
  <?php if ($teaser) { ?>
  <h2><?php print l($node->title, 'node/'.$node->nid); ?></h2>
  <p><?php print $content; ?></p>
  <?php } elseif ($page) { ?>
  <?php print theme('image', $node->icon); ?>
  <?php print t('created by !user', array('!user', theme('username', user_load($node->uid)))); ?>
  <h3>Currency rationale</h3>
  <p style="color:#<?php print $node->color; ?>"><?php print $content; ?></p>

  <h4><?php print t('Balance Limits'); ?></h4>
  <?php print theme('balance_limits', $limits); ?>

  <?php if ($node->divisions) { ?>
    <h4><?php print t('Division'); ?></h4>
    <pre><?php print $node->divisions; ?></pre>
  <?php } ?>
  <?php if ($node->ratings) { ?>
    <h4><?php print t('Ratings'); ?></h4>
    <pre><?php print $node->ratings; ?></pre>
  <?php } ?>

  <h4><?php print t('Relative value'); ?>
  <p><?php print $node->value ?></p>
  <?php } ?>
</div>
