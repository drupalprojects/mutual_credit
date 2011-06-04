<?php
// $Id: views_handler_field_mcapi_amount.inc,v 1.1.2.2 2010/12/22 20:21:51 matslats Exp $

/**
 * @file
 * Field handler to allow linking to a payee's user page
 * @ingroup views_field_handlers
 */
class views_handler_field_mcapi_amount extends views_handler_field {

  function option_definition() {
    $options = parent::option_definition();
    $options['output_type'] = array('default' => 'normal');
    return $options;
  }

  function options_form(&$form, &$form_state) {
    parent::options_form($form, $form_state);
    $options = array(
      'incoming' => t('Show only incoming'),
      'outgoing' => t('Show only outgoing'),
      'sign' => t('Change sign to minus'),
      'normal' => t('Just show amount')
    );

    if ($this->definition['group'] == t('Transaction')) {
      $form['output_type'] = array(
        '#type' => 'radios',
        '#title' => t('Output type'),
        '#description' => t('When a user arguments is passed, this option is relative to that user, otherwise, ignored'),
        '#options' => $options,
        '#default_value' => $this->options['output_type'],
      );
    }
  }
  

  function query() {
    static $i;
    $i++;
    $this->ensure_my_table();
    $this->aliases['cid'] = $this->query->add_field('mcapi_transactions', 'cid', NULL);

    // Add the field here, not in the inherited function
    //sometimes we want to add more than one instance here so we ensure they get different aliases
    $params = $this->options['group_type'] != 'group' ? array('function' => $this->options['group_type']) : array();
    $alias = 'quantity_'.$i.'_'.$this->options['output_type'];
    $this->field_alias = $this->query->add_field($this->table_alias, $this->real_field, $alias, $params);
    //these may be needed
    $this->add_additional_fields(array('payer'));
    $this->add_additional_fields(array('payee'));

    $this->add_additional_fields();
  }

  /*
  array(
    [mcapi_transactions_cid] => 1
    [mcapi_transactions_quantity] => 6
    [quantity_1_normal] => 13.66
    [mcapi_transactions_payer] => 12
    [mcapi_transactions_payee] => 7
    [quantity_2_incoming] => 13.66
    [quantity_3_outgoing] => 13.66
  )
*/
  function render($values) {
    //if we are using the 'participant' argument, the options kick in.
    //might not be the most efficient way to access the arguments
    if (isset($this->view->argument['participant']) && count($this->view->args)) {
      $key = array_search('user_exchanges', array_keys($this->view->argument));
      $uid = $this->view->args[$key];
      switch($this->options['output_type']) {
        case 'outgoing':
          if ($values->{$this->aliases['payee']} == $uid) return;
          break;
        case 'incoming':
          if ($values->{$this->aliases['payer']} == $uid) return;
          break;
        case 'sign':
          if ($values->{$this->aliases['payer']} == $uid) {
            //negate the value
            $values->{$this->field_alias} = -$values->{$this->field_alias};
          }
      }
    }
    elseif ($this->options['output_type'] != 'normal') {
      return t('n/a');
    }
    //in case there isn't a row yet in the cache table
    elseif ($values->{$this->aliases['cid']} == NULL && $values->cleared_balance == NULL) { 
      $values->{$this->aliases['cid']} = db_result(db_query("SELECT cid FROM {mcapi_currencies}"));
    }
   return mcapi_format_money($values->{$this->field_alias}, $values->{$this->aliases['cid']});
  }
}
