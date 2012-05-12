<?php
/**
 * Field handler to present a link transaction edit.
 */
class views_handler_field_transaction_link_edit extends views_handler_field_transaction_link {

  function render_link($data) {
    // ensure user has access to edit this transaction.
    $transaction = transaction_load($data);
    if (!transaction_access('update', $transaction)) {
      return '';
    }

    $this->options['alter']['make_link'] = TRUE;
    $this->options['alter']['path'] = "transaction/$transaction->serial/edit";
    $this->options['alter']['query'] = drupal_get_destination();

    return !empty($this->options['text']) ? $this->options['text'] : t('edit (as admin)');
  }
}
