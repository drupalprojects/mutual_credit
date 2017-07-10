<?php

/**
 * @file
 * Token hooks.
 */
use \Drupal\mcapi\Entity\Currency;

/**
 * Implements hook_tokens().
 */
function mcapi_tokens($type, $tokens, array $data = [], array $options = []) {

  if (!array_key_exists($type, $data) || !is_object($data[$type])) {
    return;
  }

  $replacements = [];
  if ($type == 'xaction') {
    $transaction = $data[$type];
    foreach ($tokens as $name => $original) {
      switch ($name) {
        case 'state':
          $replacements[$original] = $transaction->state->entity->label();
          break;

        case 'type':
          $replacements[$original] = $transaction->type->entity->label();
          break;

        case PAYER_FIELDNAME:
          $replacements[$original] = $transaction->payer->entity->toLink()->toString();
          break;

        case PAYEE_FIELDNAME:
          $replacements[$original] = $transaction->payee->entity->toLink()->toString();
          break;

        case 'payer-holder':
          $replacements[$original] = $transaction->payer->entity->getHolder()->toLink()->toString();
          break;

        case 'payer-holder':
          $replacements[$original] = $transaction->payee->entity->getHolder()->toLink()->toString();
          break;

        case 'creator':
          $replacements[$original] = $transaction->creator->entity->toLink()->toString();
          break;

        case 'description':
          $renderable = $transaction->description->view(['label' => 'hidden']);
          $replacements[$original] = trim(strip_tags(\Drupal::service('renderer')->renderPlain($renderable)));
          break;

        case 'created':
          $replacements[$original] = format_date($transaction->created->value, 'medium');
          break;

        case 'url':
          $replacements[$original] = $transaction->toUrl();
          break;

        case 'worth':
          $renderable = $transaction->worth->view(['label' => 'hidden']);
          $replacements[$original] = \Drupal::service('renderer')->renderRoot($renderable);
          break;

        case 'sentence':
          $renderable = \Drupal::entityTypeManager()->getViewBuilder('mcapi_transaction')->view($transaction, 'sentence');
          $replacements[$original] = \Drupal::service('renderer')->renderPlain($renderable);

      }

    }

    // Handle any entity references - tokens within tokens.
    // @todo test this
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
  }
  if ($type == 'wallet') {
    $wallet = $data[$type];
    foreach ($tokens as $name => $original) {
      switch ($name) {
        case 'owner':
          $replacements[$original] = $wallet->getOwner()->label();
          break;

        case 'owner-link':
          $replacements[$original] = $wallet->getOwner()->toLink(NULL, NULL, ['absolute' => TRUE]);
          break;

        case 'holder':
          $replacements[$original] = $wallet->getHolder()->label();
          break;

        case 'holder-link':
          $replacements[$original] = $wallet->getHolder()->toLink(NULL, NULL, ['absolute' => TRUE]);
          break;

        case 'name':
          $replacements[$original] = $wallet->label();
          break;

        case 'link':
          $replacements[$original] = $wallet->toLink(NULL, NULL, ['absolute' => TRUE]);
          break;

      }
    }
  }

  return $replacements;
}

/**
 * Implements hook_token_info().
 *
 * @todo entity token generation should be more automated.
 */
function mcapi_token_info() {
  $info = [
    'types' => [
      'xaction' => [
        'name' => t('Transactions'),
        'description' => t('Tokens related to a community accounting transactions.'),
        'needs-data' => 'mcapi_transaction',
      ],
      'wallet' => [
        'name' => t('Wallet'),
        'description'  => t('Wallet'),
        'needs_data' => 'mcapi_wallet',
      ],
    ],
  ];
  $definitions = \Drupal::service('entity_field.manager')
    ->getFieldDefinitions('mcapi_transaction', 'mcapi_transaction');
  // Chance to remove some tokens here using unset($definitions['uuid']).
  unset($definitions['parent'], $definitions['uuid'], $definitions['xid']);

  // See node_token_info();
  foreach ($definitions as $key => $def) {
    $info['tokens']['xaction'][$key] = [
      'name' => $def->getLabel(),
      'description' => $def->getDescription(),
    ];
  }

  $info['tokens']['xaction'] = [
    'url' => [
      'name' => t('Absolute link'),
    ],
    'payer-holder' => [
      'name' => t('Payer holder'),
    ],
    'payee-holder' => [
      'name' => t('Payee holder'),
    ]
  ];

  $info['tokens']['wallet'] = [
    'name' => [
      'name' => t('Wallet name'),
    ],
    'link' => [
      'name' => t('Wallet link'),
    ],
    'owner' => [
      'name' => t('Owner label'),
    ],
    'owner-link' => [
      'name' => t('Owner link'),
    ],
    'holder-name' => [
      'name' => t('Holder label'),
    ],
    'holder-link' => [
      'name' => t('Holder link'),
    ],
  ];
  return $info;
}
