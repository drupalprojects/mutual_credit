<?php

namespace Drupal\mcapi\Views;

use Drupal\views\EntityViewsData;

/**
 * Views data for wallet entity.
 */
class WalletViewsData extends EntityViewsData {

  /**
   * {@inheritdoc}
   */
  public function getViewsData() {
    $data = parent::getViewsData();
    $data['mcapi_wallet']['wid']['field']['id'] = 'mcapi_entity';

    $data['mcapi_wallet']['name']['help'] = $this->t("When the holder can hold one wallet only, the wallet inherits the name of its holder");

    $data['mcapi_wallet']['balance'] = [
      'title' => $this->t('Balance'),
      'help' => $this->t("Sum of all this wallet's credits minus debits"),
      'field' => [
        'id' => 'computed_worths',
      ]
    ];
    $data['mcapi_wallet']['volume'] = [
      'title' => $this->t('Volume of transactions'),
      'help' => $this->t("Sum of all this wallet's transactions"),
      'field' => [
        'id' => 'computed_worths',
      ]
    ];
    $data['mcapi_wallet']['gross_in'] = [
      'title' => $this->t('Gross income'),
      'help' => $this->t("Sum of all this wallet's income ever"),
      'field' => [
        'id' => 'computed_worths',
      ]
    ];
    $data['mcapi_wallet']['gross_out'] = [
      'title' => $this->t('Gross expenditure'),
      'help' => $this->t("Sum of all this wallet's outgoings ever"),
      'field' => [
        'id' => 'computed_worths',
      ]
    ];
    $data['mcapi_wallet']['trades'] = [
      'title' => $this->t('Number of trades'),
      'help' => $this->t("Number of transactions involving this wallet"),
      'field' => [
        'id' => 'mcapi_dummy',
      ]
    ];
    $data['mcapi_wallet']['partners'] = [
      'title' => $this->t('Partner count'),
      'help' => $this->t("Number of unique trading partners"),
      'field' => [
        'id' => 'mcapi_dummy',
      ]
    ];

    unset(
        $data['mcapi_wallet']['holder_entity_type'],
        $data['mcapi_wallet']['holder_entity_id']
    );
    $data['mcapi_wallet']['holder'] = [
      'title' => $this->t('Link to holding entity'),
      'help' => $this->t('Could be any entity implementing OwnerInterface'),
      'field' => [
        'id' => 'mcapi_wallet_holder',
      ]
    ];

    //join the wallet table to each of the entity tables needed.
    foreach (array_keys(\Drupal\mcapi\Mcapi::walletableBundles()) as $entity_type_id) {
      $entity_type = \Drupal::entityTypeManager()->getDefinition($entity_type_id);
      $data['mcapi_wallet'][$entity_type_id] = [
        'title' => $this->t('@entitytype holder', ['@entitytype' => $entity_type->getLabel()], ['context' => 'wallet-holding entity type']),
        'help' => $this->t('The entity which holds the wallet'),
        'relationship' => [
          'id' => 'mcapi_wallet_owner',
          'base' => $entity_type->getDataTable() ?  : $entity_type->getBaseTable(),
          'base field' => $entity_type->getKey('id'),
          'relationship field' => 'holder_entity_id',
          'holder_entity_type' => $entity_type_id,
        ]
      ];
    }

    $data['mcapi_wallet']['holder_entity_type']['field']['id'] = 'mcapi_holder_type';
    $data['mcapi_wallet']['holder_entity_type']['field']['help'] = $this->t("The wallet holder's translated EntityType name");

    // This allows us to do group queries such as find the balance per wallet.
    $data['mcapi_transactions_index']['table']['join'] = [
      'mcapi_wallet' => [
        'left_field' => 'wid',
        'field' => 'wallet_id',
        'required' => TRUE,
      ],
    ];

    //should this be in an _alter method?
    $data['users_field_data']['first_wid'] = [
      'relationship' => [
        'label' => $this->t("The user's first wallet."),
        'title' => $this->t("The user's first wallet."),
        'help' => $this->t("Reference the first wallet each user."),
        'id' => 'mcapi_user_first_wallet',
        'base' => 'mcapi_wallet',
        'base field' => 'holder_entity_id',
        'left_field' => 'wid',
        'relationship field' => 'uid',
      ]
    ];

    return $data;
  }
}
