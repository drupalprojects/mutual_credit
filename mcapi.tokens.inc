<?php

/*
 * Implements hook_tokens().
 */
function mcapi_tokens($type, $tokens, array $data = [], array $options = []) {
  if ($type != 'mcapi' || !array_key_exists('mcapi', $data) || !is_object($data['mcapi'])) {
    return [];
  }

  $replacements = [];
  $transaction = $data['mcapi'];
  module_load_include('inc', 'mcapi', 'src/ViewBuilder/theme');
  $replaced = get_transaction_vars($transaction);
  foreach ($tokens as $name => $original) {
    $replacements[$original] = $replaced[$name];
  }
  //handle any entity references - tokens within tokens
  $token_service = \Drupal::token();
  if ($payer_tokens = $token_service->findWithPrefix($tokens, 'payer')) {
    $replacements += $token_service->generate(
      'user',
      $payer_tokens,
      ['user' => $transaction->payer->target_id],
      $options
    );
  }
  if ($payee_tokens = $token_service->findWithPrefix($tokens, 'payee')) {
    $replacements += $token_service->generate(
      'user',
      $payee_tokens,
      ['user' => $transaction->payee->target_ids],
      $options
    );
  }
  if ($creator_tokens = $token_service->findWithPrefix($tokens, 'creator')) {
    $replacements += $token_service->generate(
      'user',
      $creator_tokens,
      ['user' => $transaction->creator->target_id],
      $options
    );
  }
  if ($created_tokens = $token_service->findWithPrefix($tokens, 'created')) {
    $replacements += $token_service->generate(
      'created',
      $created_tokens,
      ['created' => $transaction->created->value],
      $options
    );
  }
  return $replacements;
}

/**
 * Implements hook_token_info().
 *
 * @todo entity token generation should be more automated
 * @see https://api.drupal.org/api/drupal/modules!node!node.tokens.inc/function/node_token_info/7
 */
function mcapi_token_info() {
  $info = [
    'types' => [
      'xaction' => [
        'name' => t('Transactions'),
        'description' => t('Tokens related to a community accounting transactions.'),
        'needs-data' => 'mcapi_transaction',
      ]
    ]
  ];
  $definitions = \Drupal::service('entity_field.manager')
    ->getFieldDefinitions('mcapi_transaction', 'mcapi_transaction');
  //chance to remove some tokens here using unset($definitions['uuid']).
  unset($definitions['parent'], $definitions['uuid'], $definitions['xid']);

  //see node_token_info();
  foreach ($definitions as $key => $def) {
    $info['tokens']['xaction'][$key] = [
      'name' => $def->getLabel(),
      'description' => $def->getDescription()
    ];
  }

  $info['tokens']['xaction']['url'] = [
    'name' => t('Absolute link'),
  ];

  return $info;
}
