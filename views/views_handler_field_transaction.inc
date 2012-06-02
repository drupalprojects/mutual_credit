<?php
/**
 * @file
 * Contains the basic 'transaction' field handler.
 */

/**
 * Field handler to provide simple renderer that allows linking to a transaction.
 * Definition terms:
 * - link_to_transaction default: Should this field have the checkbox "link to transaction" enabled by default.
 *
 * @ingroup views_field_handlers
 */
class views_handler_field_transaction extends views_handler_field {

  function init(&$view, &$options) {
    parent::init($view, $options);
    //$this->additional_fields['serial'] = array('table' => 'mcapi_transactions', 'field' => 'serial');
  }

  function option_definition() {
    $options = parent::option_definition();
    if ($this->real_field == 'serial') {
      $options['link_to_transaction'] = array('default' => isset($this->definition['link_to_transaction default']) ? $this->definition['link_to_transaction default'] : FALSE);
    }
    else {
      $options['link_title'] = array('default' => $this->real_field);
    }
    return $options;
  }

  /**
   * Provide link to transaction option
   */
  function options_form(&$form, &$form_state) {
    if ($this->real_field == 'serial') {
      $form['link_to_transaction'] = array(
        '#title' => t('Link this field to the transaction'),
        '#type' => 'checkbox',
        '#default_value' => !empty($this->options['link_to_transaction']),
      );
    }
    else {
      $form['link_title'] = array(
        '#type' => 'textfield',
        '#title' => t("Text to show for link"),
        '#default_value' => $this->options['link_title'],
      );
    }
    parent::options_form($form, $form_state);
  }
  function query() {
    $this->ensure_my_table();
    $this->add_additional_fields(array('serial'));
  }

  function render($values) {
    $serial = $values->{$this->aliases['serial']};
    $transaction = transaction_load($serial);
    $op = $this->real_field == 'serial' ? 'view' : $this->real_field;
    $access_callback = 'transaction_'.$op.'_access';
    if ($access_callback($transaction)) {
      return array('#theme' => 'link',
        '#text' => $this->real_field == 'serial' ? $serial : $this->options['link_title'],
        '#path' => 'transaction/'.$serial.'/'.$op,
        '#options' => array(
          'attributes' =>array(),
          'html' => TRUE
        )
      );
    }
  }

}
