<?php


/*
 * implements hook_token_info_alter
 */
function mcapi_signatures_token_info_alter(&$items) {
  $items['tokens']['transaction']['sign_link'] = array(
    'name' => t('Sign link'),
    'description' => t('A context-sensitive link inviting the user to sign or sign off, the transaction')
  );
}

/*
 * implements hook_tokens
 */
function mcapi_signatures_tokens($type, $tokens, array $data = array(), array $options = array()) {
  if ($type != 'transaction') return;
  $transaction = &$data['transaction'];
  if (isset($tokens['sign_link'])) {
    $links = array_filter(array(_get_sign_link($transaction), _get_signoff_link($transaction)));
    return array($tokens['sign_link'] => render($links));
  }
}
