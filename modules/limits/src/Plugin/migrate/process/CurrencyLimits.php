<?php

/**
 * @file
 */
namespace Drupal\mcapi_limits\Plugin\migrate\process;
use Drupal\migrate\MigrateSkipRowException;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;

/**
 * convert the old display setting into the new one-string format
 *
 * @MigrateProcessPlugin(
 *   id = "d7_mcapi_currency_limits"
 * )
 */
class CurrencyLimits extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   *
   * $value is an object containing the per-currency limit setings like this [
   *   personal (bool) //whether to allow personal overrides
   *   skip -> [ // circumstances on which to skip checking.
   *     auto (bool) // on transactions of type auto
   *     mass (bool) // on transactions of type mass
   *     user1 (bool)// when user 1 is involved
   *     owner (bool) // when the currency owner is involved
   *     reservoir (bool) // when the reservoir account is involved
   *   ]
   *   limits_callback (string)
   *   limits_global (array) //settings for callback with the same name
   *   limits_equations (array) //settings for callback with the same name
   * ]
   *
   * Maps to [
   *   personal (bool)
   *   skip:
   *     auto (bool)
   *     mass (bool)
   *     user1 (bool)
   *     owner (bool)
   *   prevent (bool)
   *   warning_mail (mail)
   *   prevented_mail (mail)
   * ]
   * //We ned to pass the plugin settings over as well though.
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
     if($value->limits_callback == 'limits_none') {
       throw new MigrateSkipRowException('No balance limits on currency '.$row->info['name']);
     }
     $limits['personal']= $value->personal;
     unset($value->skip['reservoir']);
     $limits['skip'] = $value->skip;
     $limits['prevent'] = TRUE;
     $limits['warning_mail'] = [];
     $limits['prevented_mail'] = ['subject' => '', 'body' => ''];

     switch($value->limits_callback) {
       case 'limits_global':
         $limits['plugin'] = 'explicit';
         $limits['plugin_settings'] = $value->limits_global; //min and max
         break;

       case 'limits_equations':
         $limits['plugin'] = 'calculated';
         $old = ['@gross_in, @gross_out, @trades'];
         $new = ['@in', '@out', '@num'];
         $limits['plugin_settings'] = [
           'max_formula' => str_replace($old, $new, $value->limits_equations['max_formula']),
           'min_formula' => str_replace($old, $new, $value->limits_equations['min_formula'])
         ];
         break;

       default:
         throw new MigrateSkipRowException('Unknown limits plugin on currency '.$row->info['name'] .': Limits not migrated');
     }
    return $limits;
  }

}
