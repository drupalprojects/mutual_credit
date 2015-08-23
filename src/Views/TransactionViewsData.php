<?php

/**
 * @file
 * Contains \Drupal\mcapi\Views\TransactionViewsData.
 *
 */

namespace Drupal\mcapi\Views;

use Drupal\views\EntityViewsData;

class TransactionViewsData extends EntityViewsData {

  /**
   * {@inheritdoc}
   * @todo see Drupal\taxonomy\TermViewsData to see how an index table can be incporated after beta 11
   */
  public function getViewsData() {
    $data = parent::getViewsData();

    $data['mcapi_transaction']['table']['base']['defaults']['field'] = 'xid';
    $data['mcapi_transaction']['table']['wizard_id'] = 'transactions';
    $data['mcapi_transaction']['table']['access query tag'] = 'mcapi_views_access';

    $data['mcapi_transaction']['state']['field']['id'] = 'mcapi_state';
    $data['mcapi_transaction']['state']['filter'] = [
      'id' => 'in_operator',
      'options callback' => 'mcapi_entity_label_list',
      'options arguments' => ['mcapi_state']
    ];
    $data['mcapi_transaction']['type']['field']['id'] = 'mcapi_type';
    $data['mcapi_transaction']['type']['filter'] = [
      'id' => 'in_operator',
      'options callback' => 'mcapi_entity_label_list',
      'options arguments' => ['mcapi_type']
    ];

    $data['mcapi_transaction']['parent']['field']['id'] = 'boolean';
    $data['mcapi_transaction']['parent']['filter']['id'] = 'boolean';
    unset(
      $data['mcapi_transaction']['parent']['argument'],
      $data['mcapi_transaction']['parent']['sort'],
      $data['mcapi_transaction']['parent']['entity field']
    );


    $data['mcapi_transaction']['payer']['filter']['id'] = 'wallet_name';
    $data['mcapi_transaction']['payee']['filter']['id'] = 'wallet_name';
    $data['mcapi_transaction']['creator']['filter']['id'] = 'user_name';
    /**
    // @todo Add similar support to any date field
    // @see https://www.drupal.org/node/2337507
    // @see NodeViewsData
    $data['mcapi_transaction']['created_year_month'] = [
      'title' => $this->t('Created year + month'),
      'help' => $this->t('Date in the form of YYYYMM.'),
      'argument' => [
        'field' => 'created',
        'id' => 'date_year_month',
      ],
    ];

    $data['mcapi_transaction']['created_year'] = [
      'title' => $this->t('Created year'),
      'help' => $this->t('Date in the form of YYYY.'),
      'argument' => [
        'field' => 'created',
        'id' => 'date_year',
      ],
    ];
     *
     */

    //virtual fields
    $data['mcapi_transaction']['transitions'] = [
      'title' => $this->t('Transitions'),
      'help' => $this->t('What the user can do to the transaction'),
      'field' => [
        'id' => 'transaction_transitions',
      ]
    ];

    //@todo I don't know why these relationships aren't coming automatically when 'creator' is
    $data['mcapi_transaction']['payer']['help'] = $this->t('The wallet which was debited');
    $data['mcapi_transaction']['payer']['relationship'] = [
      'base' => 'mcapi_wallet',
      'base field' => 'wid',
      //'label' => 'Payer wallet',
      'title' => 'Payer',
      'id' => 'standard'
    ];
    $data['mcapi_transaction']['payer']['help'] = $this->t('The wallet which was credited');
    $data['mcapi_transaction']['payee']['relationship'] = [
      'base' => 'mcapi_wallet',
      'base field' => 'wid',
      //'label' => 'Payee wallet',
      'title' => 'Payee',
      'id' => 'standard'
    ];

    /**
    //@todo consider why the index table isn't being found by views
    // Load all typed data definitions of all fields. This should cover each of
    // the entity base, revision, data tables.
    $field_definitions = \Drupal\mcapi\Storage\TransactionStorageSchema::getTransactionIndexSchema();

    //$table_mapping = $this->storage->getTableMapping();
    foreach ($table_mapping->getFieldNames('mcapi_transactions_index') as $field_name) {
      $this->mapFieldDefinition('mcapi_transactions_index', $field_name, $field_definitions[$field_name], $table_mapping, $data[$table]);
    }
    $data['mcapi_transactions_index']['table']['entity type'] = 'mcapi_transaction';

     *
    */

    $data['mcapi_transactions_index']['table'] = [
      'group' => $this->t('Transaction index'),
      'entity type' => 'mcapi_transaction',
      'base' => [
        'field' => 'xid',
        'title' => $this->t('Transaction index'),
        'help' => $this->t('Transaction index table'),
        'access query tag' => 'mcapi_views_access',
        'weight' => 5,
        'defaults' => []
      ],
      'wizard_id' => 'transaction_index', //this links it to the wizard plugin
    ];

    $data['mcapi_transactions_index']['serial'] = [
      'title' => $this->t('Serial'), // The item it appears as on the UI,
      'help' => $this->t('The serial number of the transaction and dependents'),
      'field' => [
        'id' => 'standard',
      ],
      'sort' => [
        'id' => 'standard',
      ],
    ];
    $data['mcapi_transactions_index']['xid'] = [
      'title' => $this->t('Transaction id'), // The item it appears as on the UI,
      'help' => $this->t('The unique database key of the transaction'),
      'field' => [
        'id' => 'field', //this might be 'transaction' if it exists
      ],
      'sort' => [
        'id' => 'standard',
      ],
      'entity field' => 'xid'
    ];
    //the wallet_id and partner_id are being used in a limited way here.
    //with new handlers, would allow some interesting possibilities
    $data['mcapi_transactions_index']['wallet_id'] = [
      'title' => $this->t('Wallet ID'),
      'help' => $this->t('The wallet we are looking at'),
      'relationship' => [
        'id' => 'standard',
        'base' => 'mcapi_wallet',
        'base field' => 'wid',
        'label' => t('1st wallet'),
        'relationship field' => 'wallet_id'
      ],
      'filter' => [
        'id' => 'wallet_name',
      ],
      'argument' => [
        'id' => 'standard',
      ],
      'sort' => [
        'id' => 'standard',
      ],
    ];
    $data['mcapi_transactions_index']['partner_id'] = [
      'title' => $this->t('Partner wallet ID'),
      'help' => $this->t('The wallet the 1st user traded with'),
      'relationship' => [
        'id' => 'standard',
        'base' => 'mcapi_wallet',
        'base field' => 'wid',
        'label' => t('1st wallet'),
        'relationship field' => 'partner_id'
      ],
      'field' => [
        'id' => 'mcapi_wallet_label',
      ],
      'filter' => [
        'id' => 'wallet_name',
      ],
      'sort' => [
        'id' => 'standard',
      ],
    ];

    $data['mcapi_transactions_index']['state'] = [
      'title' => $this->t('State'),
      'help' => $this->t('The name of the workflow state of the transaction.') . ' ' . t("'Counted' states only."),
      'field' => [
        'id' => 'mcapi_state',
      ],
      'filter' => [
        'id' => 'in_operator',
        'options callback' => 'mcapi_entity_label_list',
        'options arguments' => ['mcapi_state']
      ],
      'sort' => [
        'id' => 'standard',
      ],
    ];

    $data['mcapi_transactions_index']['type'] = [
      'title' => $this->t('Type'),
      'help' => $this->t('Which form or module which created the transaction.'),
      'field' => [
        'id' => 'standard',
      ],
      'filter' => [
        'id' => 'in_operator',
        'options callback' => 'mcapi_entity_label_list',
        'options arguments' => ['mcapi_type']
      ],
      'sort' => [
        'id' => 'standard',
      ],
    ];
    $data['mcapi_transactions_index']['created'] = [
      'title' => $this->t('Created'),
      'help' => $this->t('The second the transaction was created.'),
      'field' => [
        'id' => 'date',
      ],
      'sort' => [
        'id' => 'date'
      ],
      'filter' => [
        'id' => 'date',
      ],
    ];
    $data['mcapi_transactions_index']['created_year_month'] = [
      'title' => $this->t('Created year + month'),
      'help' => $this->t('Date in the form of YYYYMM.'),
      'argument' => [
        'field' => 'created',
        'id' => 'date_year_month',
      ],
    ];

    $data['mcapi_transactions_index']['created_year'] = [
      'title' => $this->t('Created year'),
      'help' => $this->t('Date in the form of YYYY.'),
      'argument' => [
        'field' => 'created',
        'id' => 'date_year',
      ],
    ];
    $data['mcapi_transactions_index']['incoming'] = [
      'title' => $this->t('Income'),
      'help' => $this->t('The income from the transaction; positive or zero'),
      'field' => [
        'id' => 'worth',
        'additional fields' => ['curr_id']
      ],
      'filter' => [
        'id' => 'numeric',
      ],
      'sort' => [
        'id' => 'numeric',
      ],
    ];
    $data['mcapi_transactions_index']['outgoing'] = [
      'title' => $this->t('Expenditure'),
      'help' => $this->t('The outgoing quantity of the transaction; positive or zero'),
      'field' => [
        'id' => 'worth',
        'additional fields' => ['curr_id']
      ],
      'filter' => [
        'id' => 'numeric',
      ],
      'sort' => [
        'id' => 'numeric',
      ],
    ];
    $data['mcapi_transactions_index']['diff'] = [
      'title' => $this->t('Change in balance'),
      'help' => $this->t('The difference to the balance; positive or negative'),
      'field' => [
        'id' => 'worth',
        'additional fields' => ['curr_id']
      ],
      'filter' => [
        'id' => 'numeric',
      ],
      'sort' => [
        'id' => 'numeric',
      ],
    ];
    $data['mcapi_transactions_index']['volume'] = [
      'title' => $this->t('Volume'),
      'help' => $this->t('The quantity of the transaction; always positive'),
      'field' => [
        'id' => 'worth',
        'additional fields' => ['curr_id']
      ],
      'filter' => [
        'id' => 'numeric',
      ],
      'sort' => [
        'id' => 'numeric',
      ],
    ];
    $data['mcapi_transactions_index']['curr_id'] = [
      'title' => $this->t('Currency'),
      'help' => $this->t('The currency'),
      'field' => [
        'id' => 'standard',
      ],
      'filter' => [
        'id' => 'in_operator',
        'options callback' => 'mcapi_entity_label_list',
        'options arguments' => ['mcapi_currency']
      ],
      'argument' => [
        'id' => 'standard',
      ],
    ];

    $data['mcapi_transactions_index']['child'] = [
      'title' => $this->t('Is a child'),
      'help' => $this->t('FALSE if the transaction is the main one with that serial number'),
      'field' => [
        'id' => 'boolean',
      ],
      'filter' => [
        'id' => 'boolean',
      ],
    ];

    //virtual fields
    $data['mcapi_transactions_index']['balance'] = [
      'title' => $this->t('Running balance'),
      'help' => $this->t("The sum of the wallet's previous transactions."),
      'field' => [
        'id' => 'balance',
        'additional fields' => ['curr_id']
      ],
    ];
    $data['mcapi_transactions_index']['transitions'] = [
      'title' => $this->t('Transitions'),
      'help' => $this->t('What the user can do to the transaction'),
      'field' => [
        'id' => 'transaction_transitions',
      ]
    ];
    return $data;
  }

}
