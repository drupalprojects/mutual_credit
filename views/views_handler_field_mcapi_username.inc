<?php
// $Id: views_handler_field_mcapi_trader.inc,v 1.1.2.2 2010/12/22 20:21:51 matslats Exp $
/**
 * @file
 * Field handler to allow linking to a payer's user page
 * @ingroup views_field_handlers
 */
class views_handler_field_mcapi_username extends views_handler_field {

  function option_definition() {
    $options = parent::option_definition();
    $options['link_to_user'] = array('default' => TRUE);
    return $options;
  }

  function options_form(&$form, &$form_state) {
    $form['link_to_user'] = array(
      '#title' => t("Link this field to the user"),
      '#type' => 'checkbox',
      '#default_value' => $this->options['link_to_user'],
    );
    parent::options_form($form, $form_state);
  }


  function render_link($uid, $values) {
    $this->options['alter']['make_link'] = TRUE;
    $this->options['alter']['path'] = "user/" . $uid;
  }

  function render($values) {
    $uid = $this->get_value($values);
    if (!empty($this->options['link_to_user']) && user_access('access user profiles') && !empty($uid)) {
      $this->render_link($uid, $values);
    }
    return format_username(user_load($uid));
  }
}