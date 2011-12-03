<?php
/*
 * Implements ctools hook default_mcapi_forms
 * adds the mail template to the default forms (only those which haven't been overridden)
 */
function mcapi_mail_mcapiforms_default_alter(&$mcapiforms) {
  foreach ($mcapiforms as $name => $mcapi_form) {
    $mcapiforms[$name]->data['email_template'] = array(
      'subject' => _mcapi_mail_text('subject'),
      'body' => _mcapi_mail_text('body')
    );
  }
}