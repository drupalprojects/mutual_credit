<?php
// $Id$
/**
 * Filter handler for exchange types
 */
class views_handler_filter_mc_exchange_types extends views_handler_filter_many_to_one {

  //this is waiting for a suitable hook function so that modules can declare their own exchange types.
  function get_value_options() {
    $this->value_options = array(
      '3rdparty' => '3rdparty',
      'incoming direct'=>'incoming direct',
      'incoming confirm' => 'incoming confirm',
      'outgoing direct' => 'outgoing direct',
      'outgoing confirm'=> 'outgoing confirm',
      'many2one' => 'many2one',
      'one2many' => 'one2many',
      'few2one' =>'few2one',
      'one2few' => 'one2few',
    );
  }
}
