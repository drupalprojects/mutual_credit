<?php
// $Id: views_handler_filter_transaction_types.inc,
/**
 * Filter handler for transaction types
 */
class views_handler_filter_transaction_types extends views_handler_filter_many_to_one {
  function get_value_options() {
    $this->value_options = array(
      'incoming_direct'=>'incoming_direct',
      'incoming_confirm' => 'incoming_confirm',
      'outgoing_direct' => 'outgoing_direct',
      'outgoing_confirm'=> 'outgoing_confirm',
      '3rdparty_direct' => '3rdparty_direct',
      'outgoing_mass_direct' => 'outgoing_mass_direct',
      'outgoing_mass_confirm' => 'outgoing_mass_confirm',
      'incoming_mass_direct' =>'incoming_mass_direct',
      'incoming_mass_confirm' => 'incoming_mass_confirm',
      'api unspecified' => 'api unspecified'
	  );
  }
}
