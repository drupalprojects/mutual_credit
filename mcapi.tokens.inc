<?php

/*
 * Implements hook_tokens().
 * tokens apply to transaction entity only
 */
function mcapi_tokens($type, $tokens, array $data = [], array $options = []) {

  if ($type != 'xaction' || !array_key_exists($type, $data) || !is_object($data[$type])) {
    return [];
  }
  $replacements = [];
  $transaction = $data[$type];

  foreach ($tokens as $name => $original) {
    switch ($name) {
      case 'state':
        $replacements[$original] = $transaction->state->entity->label();
        break;
      case 'type':
        $replacements[$original] = $transaction->type->entity->label();
        break;
      case 'payer':
        $replacements[$original] = $transaction->payer->entity->getHolder()->link();
        break;
      case 'payee':
        $replacements[$original] = $transaction->payee->entity->getHolder()->link();
        break;
      case 'creator':
        $replacements[$original] = $transaction->creator->entity->link();
        break;
      case 'description':
        $replacements[$original] = $transaction->description->getString();
        break;
      case 'created':
        $replacements[$original] = format_date($transaction->created->value, 'medium');
        break;
      case 'url':
        $replacements[$original] = $transaction->url('canonical');
        break;
      //the field module did this in d7 but can't find where to debug in d8
      case 'worth':
        $replacements[$original] = (string)$transaction->worth;
    }
  }

  //handle any entity references - tokens within tokens
  //@todo test this
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
