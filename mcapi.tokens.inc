<?php

use Drupal\mcapi\Mcapi;

/*
 * implements hook_tokens().
 */
function mcapi_tokens($type, $tokens, array $data = [], array $options = []) {
  if ($type != 'mcapi' || !array_key_exists('mcapi', $data) || !is_object($data['mcapi'])) {
    return [];
  }

  $replacements = $replaced = [];
  $transaction = $data['mcapi'];
  Mcapi::processTransactionVars($replaced, $transaction);
  foreach ($tokens as $name => $original) {
    $replacements[$original] = $replaced[$name];
  }
  //TODO there seems to be some confusion about when something is a render array and when rendered
  $replacements['[mcapi:worth]'] = drupal_render($replacements['[mcapi:worth]']);//hack

  //handle any entity references - tokens within tokens
  $token_service = \Drupal::token();
  if ($payer_tokens = $token_service->findWithPrefix($tokens, 'payer')) {
    $replacements += $token_service->generate(
      'user',
      $payer_tokens,
      array('user' => $transaction->payer->target_id),
      $options
    );
  }
  if ($payee_tokens = $token_service->findWithPrefix($tokens, 'payee')) {
    $replacements += $token_service->generate(
      'user',
      $payee_tokens,
      array('user' => $transaction->payee->target_id),
      $options
    );
  }
  if ($creator_tokens = $token_service->findWithPrefix($tokens, 'creator')) {
    $replacements += $token_service->generate(
      'user',
      $creator_tokens,
      array('user' => $transaction->creator->target_id),
      $options
    );
  }
  if ($created_tokens = $token_service->findWithPrefix($tokens, 'created')) {
    $replacements += $token_service->generate(
      'created',
      $created_tokens,
      array('created' => $transaction->created->value),
      $options
    );
  }
  return $replacements;
}

/**
 * implements hook_token_info().
 */
function mcapi_token_info() {
  $info = array(
    'types' => array(
      'mcapi' => array(
        'name' => t('Transactions'),
        'description' => t('Tokens related to a community accounting transactions.'),
        'needs-data' => 'mcapi_transaction',
      )
    )
  );
  $definitions = \Drupal::entityManager()->getFieldDefinitions('mcapi_transaction', 'mcapi_transaction');
  //chance to remove some tokens here using unset($definitions['uuid']).

  //this really should be more automated in core
  foreach ($definitions as $key => $def) {
    $info['tokens']['mcapi'][$key] = array(
      'name' => $def->getLabel(),
      'description' => $def->getDescription()
    );
  }
  //add a couple more tokens for virtual fields
  $info['tokens']['mcapi']['links'] =  array(
    'name' => t('Links'),
    'description' => t("Links to transaction transitions"),
  );
  $info['tokens']['mcapi']['url'] =  array(
    'name' => t('URL'),
  );

  return $info;
}
