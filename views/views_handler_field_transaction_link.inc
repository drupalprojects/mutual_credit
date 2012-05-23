<?php
/**
 * Field handler to present a link to the transaction.
 */
class views_handler_field_transaction_link extends views_handler_field {
  
  function option_definition() {
    $options = parent::option_definition();

    $options['view_link_title'] = array('default' => 'View', 'translatable' => TRUE);
    $options['undo_link_title'] = array('default' => 'Undo', 'translatable' => TRUE);

    return $options;
  }

  function options_form(&$form, &$form_state) {
    parent::options_form($form, $form_state);
    $form['view_link_title'] = array(
      '#type' => 'textfield',
      '#title' => t("Text to show for 'view' link"),
      '#description' => t('Leave blank to hide') .' '. t("Permissions are checked on each transaction"),
      '#default_value' => $this->options['view_link_title'],
    );
    $form['undo_link_title'] = array(
      '#type' => 'textfield',
      '#title' => t("Text to show for 'undo' link"),
      '#description' => t('Leave blank to hide') .' '. t("Permissions are checked on each transaction"),
      '#default_value' => $this->options['undo_link_title'],
    );
  }

  function query() {
    $this->ensure_my_table();
    $this->add_additional_fields(array('serial'));
  }

  function render($values) {
    $serial = $values->{$this->aliases['serial']};
    $transaction = transaction_load($serial);
    $links = array();
    if ($this->options['view_link_title']) {
      if (transaction_view_access($transaction)) {
        $links[] = array(
          'title' => $this->options['view_link_title'],
          'href' => 'transaction/'.$serial
        );
      }
    }
    //todo sort this out
    if (transaction_undo_access($transaction) && $this->options['undo_link_title']) {
      $links[] = array(
        'title' => $this->options['undo_link_title'],
        'href' => 'transaction/'.$serial.'/undo'
      );
    }
   return theme('links', array('links' => $links));
  }
}
