<?php
// $Id: views_handler_field_mcapi_description.inc
/**
 * @file
 * Contains the basic 'transaction' field handler.
 */

/**
 * Field handler to provide simple renderer that allows linking to a transaction.
 */
class views_handler_field_mcapi_description extends views_handler_field {

  function option_definition() {
    $options = parent::option_definition();
    $options['link_to_transaction'] = array('default' => TRUE);
    return $options;
  }

  /**
   * Provide link to transaction option
   */
  function options_form(&$form, &$form_state) {
    parent::options_form($form, $form_state);
    $form['link_to_transaction'] = array(
      '#title' => t('Link this field to its transaction'),
      '#description' => t('This will override any other link you have set.'),
      '#type' => 'checkbox',
      '#default_value' => !empty($this->options['link_to_transaction']),
    );
  }

  function query() {
    parent::query();
    if (!empty($this->options['link_to_transaction'])) {
      $this->add_additional_fields(array('xid'));
    }
  }


  /**
   * Render whatever the data is as a link to the transaction.
   *
   * Data should be made XSS safe prior to calling this function.
   */
  function render_link($data, $values) {
    if (!empty($this->options['link_to_transaction'])) {
      if ($data !== NULL && $data !== '') {
        $this->options['alter']['make_link'] = TRUE;
        $this->options['alter']['path'] = "transaction/" . $values->{$this->aliases['xid']};
        if (isset($this->aliases['language'])) {
          $languages = language_list();
          if (isset($languages[$values->{$this->aliases['language']}])) {
            $this->options['alter']['language'] = $languages[$values->{$this->aliases['language']}];
          }
          else {
            unset($this->options['alter']['language']);
          }
        }
      }
      else {
        $this->options['alter']['make_link'] = FALSE;
      } 
    }
    return $data;
  }

  function render($values) {
    return $this->render_link(check_plain($values->{$this->field_alias}), $values);
  }
}
