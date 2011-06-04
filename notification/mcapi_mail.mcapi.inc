mcapi_mail.mcapi.inc is included<?php
/*
 * Implements ctools hook default_mcapi_webforms
 * adds the mail template to the default webforms (only those which haven't been overridden)
 */
function mcapi_mail_default_mcapi_webforms_alter(&$webforms) {
  foreach ($webforms as $path => $webform) {
    $webforms[$path]->data['email_template'] = array(
      'subject' => _mcapi_mail_text('subject'),
      'body' => _mcapi_mail_text('body'),
      'send' => TRUE,
    );
  }
}