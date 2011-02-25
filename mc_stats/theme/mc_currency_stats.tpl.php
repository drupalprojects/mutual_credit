<?php

/*
 * template for theme callback 'mc_currency_stats';
 * used on the economy page
 * $cid
 */

print theme(
  'mc_stat',
  t('Highest incomes'),
  mc_theme_cache_stat('incomes', 5, $cid)
);
print theme(
  'mc_stat',
  t('Highest spenders'),
  mc_theme_cache_stat('expenditures', 5, $cid)
);
print theme(
  'mc_stat',
  t('Highest Volumes'),
  mc_theme_cache_stat('volumes', 5, $cid)
);
print theme(
  'mc_stat',
  t('Most Trades'),
  mc_theme_cache_stat('exchanges', 5, $cid)
);

print theme(
  'mc_stat',
  t('Weekly volumes'),
    mc_theme_cache_stat('period_volumes', 5, $cid, 'yW')
);
print theme(
  'mc_stat',
  t('Weekly trades'),
    mc_theme_cache_stat('period_exchanges', 5, $cid, 'yW')
);

?>