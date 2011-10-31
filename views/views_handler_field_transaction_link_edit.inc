<?php
/**
 * Field handler to present a link transaction edit.
 */
class views_handler_field_transaction_link_edit extends views_handler_field_transaction_link {
  function construct() {
    parent::construct();
    $this->additional_fields['creator'] = 'creator';
    $this->additional_fields['payer'] = 'payer';
    $this->additional_fields['payer'] = 'payee';
    $this->additional_fields['state'] = 'state';
  }

  function render_link($data, $values) {
    // ensure user has access to edit this transaction.
    $transaction = transaction_load($this->get_value($values, 'xid'));
    if (!transaction_access('update', $transaction)) {
      return;
    }

    $this->options['alter']['make_link'] = TRUE;
    $this->options['alter']['path'] = "transaction/$transaction->xid/edit";
    $this->options['alter']['query'] = drupal_get_destination();

    $text = !empty($this->options['text']) ? $this->options['text'] : t('edit');
    return $text;
  }
}
