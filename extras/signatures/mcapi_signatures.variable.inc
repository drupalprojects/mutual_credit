<?

/*
 * implements hook_variable
 */
function mcapi_signatures_variable() {
  $variables = array(
    'mcapi_sign_notify_template' => array(
      'title' => t('Transaction signature notification template'),
      'description' => t('A mail template (subject, body) for alerting users their signature is needed.'),
      'type' => 'array',
    ),
    /*
    'mcapi_signed_off_notify_template' => array(
      'title' => t('Transaction sign off notification template'),
      'description' => t('A mail template (subject, body) for alerting users a transaction has been signed off.'),
      'type' => 'array',
    ),
     * 
     */
  );
  $types = _get_signable_transaction_types();
  foreach ($types as $type) {
    $var_name = 'mcapi_signatures_'.$type;
    $variables[$var_name] = array(
      'title' => t('Signature settings for transaction type: @type', array('@type' => $var_name)),
      'description' => t('Specifies who should sign and who should be notified for transactions of this type'),
      'type' => 'array',
    );
  }
}