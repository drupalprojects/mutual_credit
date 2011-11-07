<?php
/*
 * Implements ctools hook default_mcapi_forms
 * adds the mail template to the default forms (only those which haven't been overridden)
 */
function mcapi_mail_default_mcapiforms_alter(&$mcapi_forms) {
  foreach ($mcapi_forms as $path => $mcapi_form) {
    $mcapi_forms[$path]->data['email_template'] = array(
      'subject' => _mcapi_mail_text('subject'),
      'body' => _mcapi_mail_text('body'),
      'send' => TRUE,
    );
  }
}
