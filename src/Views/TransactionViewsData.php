<?php

/**
 * @file
 * Contains \Drupal\mcapi\Views\TransactionViewsData.
 *
 */

namespace Drupal\mcapi\Views;

use Drupal\views\EntityViewsDataInterface;

//if (!class_exists('TransactionViewsData')) {//TODO remove this I don't know how it is included twice
class TransactionViewsData implements EntityViewsDataInterface {

  /**
   * {@inheritdoc}
   */
  public function getViewsData() {
    $data = [];

    $data['mcapi_transaction']['table'] = [
      'group' => t('Transaction'),
      'entity type' => 'mcapi_transaction',
      'access query tag' => 'mcapi_views_access',
      'base' => [
        'field' => 'xid',
        'title' => t('Transactions'),
        'help' => t('Records of transactions between wallets'),
        'weight' => 5,
        'defaults' => [
          //'field' => 'serial',//only base field itself works at the moment
          'field' => 'xid',
        ]
      ],
      'wizard_id' => 'transactions', //this links it to the wizard plugin
    ];


    $data['mcapi_transaction']['serial'] = [
      'title' => t('Serial'), // The item it appears as on the UI,
      'help' => t('The serial number of the transaction and dependents'),
      'field' => [
        'id' => 'standard',
      ],
      'sort' => [
        'id' => 'standard',
      ],
    ];
    $data['mcapi_transaction']['xid'] = [
      'title' => t('Transaction id'), // The item it appears as on the UI,
      'help' => t('The unique database key of the transaction'),
      'field' => [
        'id' => 'mcapi_entity', //this might be 'transaction' if it exists
      ],
      'sort' => [
        'id' => 'standard',
      ],
    ];

    $data['mcapi_transaction']['payer'] = [
      'title' => t('Payer'),
      'help' => t('The giving wallet'),
      'relationship' => [
        'id' => 'standard',
        'base' => 'mcapi_wallet',
        'field' => 'wid',
        'label' => t('Payer'),
        'relationship field' => 'payer'
      ],
      'filter' => [
        'id' => 'standard',
      ],
      'argument' => [
        'id' => 'standard',
      ],
      'field' => [
        'id' => 'standard',
      ],
    ];
    $data['mcapi_transaction']['payee'] = [
      'title' => t('Payee'),
      'help' => t('The receiving wallet'),
      'relationship' => [
        'id' => 'standard',
        'base' => 'mcapi_wallet',
        'field' => 'wid',
        'label' => t('Payee'),
        'relationship field' => 'payee'
      ],
      'filter' => [
        'id' => 'standard',
      ],
      'argument' => [
        'id' => 'standard',
      ],
      'field' => [
        'id' => 'standard',
      ],
    ];
    $data['mcapi_transaction']['creator'] = [
      'title' => t('Creator'),
      'help' => t('The user who first created the transaction.'),
      'relationship' => [
        'id' => 'standard',
        'base' => 'users',
        'field' => 'uid',
        'label' => t('Creator'),
        'relationship field' => 'creator',
        'filter' => ['id' => 'user_name']
      ],
      'filter' => [
        'id' => 'user_name',
      ],
      'argument' => [
        'id' => 'user_uid',
      ],
      'field' => [
        'id' => 'standard',
      ],
    ];
    $data['mcapi_transaction']['description'] = [
      'title' => t('Description'),
      'help' => t('A one line description of what was exchanged.'),
      'filter' => [
        'id' => 'string',
      ],
      'argument' => [
        'id' => 'string',
      ],
      'field' => [
        'id' => 'mcapi_description',
      ],
    ];

    $data['mcapi_transaction']['state'] = [
      'title' => t('State'),
      'help' => t('The name of the workflow state of the transaction'),
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

    $data['mcapi_transaction']['type'] = [
      'title' => t('Type'),
      'help' => t('Which form or module which created the transaction'),
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
    $data['mcapi_transaction']['parent'] = [
      'title' => t('Parent'),
      'help' => t('Whether the transaction has a parent.'),
      'field' => [
        'id' => 'boolean',
      ],
      'filter' => [
        'id' => 'boolean',
      ],
    ];
    $data['mcapi_transaction']['created'] = [
      'title' => t('Created'),
      'help' => t('The second the transaction was created.'),
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
    $data['mcapi_transaction']['changed'] = [
      'title' => t('Changed'),
      'help' => t('The second the transaction was last saved.'),
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
    $data['mcapi_transaction']['created_year_month'] = [
      'title' => t('Created year + month'),
      'help' => t('Date in the form of YYYYMM.'),
      'argument' => [
        'field' => 'created',
        'id' => 'date_year_month',
      ],
    ];

    $data['mcapi_transaction']['created_year'] = [
      'title' => t('Created year'),
      'help' => t('Date in the form of YYYY.'),
      'argument' => [
        'field' => 'created',
        'id' => 'date_year',
      ],
    ];
    //virtual fields
    $data['mcapi_transaction']['transitions'] = [
      'title' => t('Transitions'),
      'help' => t('What the user can do to the transaction'),
      'field' => [
        'id' => 'transaction_transitions',
      ]
    ];

    $data['mcapi_transactions_index']['table'] = [
      'group' => t('Transaction index'),
      'entity type' => 'mcapi_transaction',
      'base' => [
        'field' => 'xid',
        'title' => t('Transaction index'),
        'help' => t('Transaction index table'),
        'access query tag' => 'mcapi_views_access',
        'weight' => 5,
        'defaults' => [
          //'field' => 'serial',//only base field itself works at the moment
          'field' => 'xid',
        ]
      ],
      'wizard_id' => 'transaction_index', //this links it to the wizard plugin
    ];

    $data['mcapi_transactions_index']['serial'] = [
      'title' => t('Serial'), // The item it appears as on the UI,
      'help' => t('The serial number of the transaction and dependents'),
      'field' => [
        'id' => 'standard',
      ],
      'sort' => [
        'id' => 'standard',
      ],
    ];
    $data['mcapi_transactions_index']['xid'] = [
      'title' => t('Transaction id'), // The item it appears as on the UI,
      'help' => t('The unique database key of the transaction'),
      'field' => [
        'id' => 'mcapi_entity', //this might be 'transaction' if it exists
      ],
      'sort' => [
        'id' => 'standard',
      ],
    ];
    //the wallet_id and partner_id are being used in a limited way here.
    //with new handlers, would allow some interesting possibilities
    $data['mcapi_transactions_index']['wallet_id'] = [
      'title' => t('Wallet ID'),
      'help' => t('The wallet we are looking at'),
      'relationship' => [
        'id' => 'standard',
        'base' => 'mcapi_wallet',
        'field' => 'wid',
        'label' => t('1st wallet'),
        'relationship field' => 'wallet_id'
      ],
      'argument' => [
        'id' => 'standard',
      ],
      'sort' => [
        'id' => 'standard',
      ],
    ];
    $data['mcapi_transactions_index']['partner_id'] = [
      'title' => t('Partner wallet ID'),
      'help' => t('The wallet the 1st user traded with'),
      'relationship' => [
        'id' => 'standard',
        'base' => 'mcapi_wallet',
        'field' => 'wid',
        'label' => t('1st wallet'),
        'relationship field' => 'partner_id'
      ],
      'field' => [
        'id' => 'mcapi_wallet_label',
      ],
      'sort' => [
        'id' => 'standard',
      ],
    ];

    $data['mcapi_transactions_index']['state'] = [
      'title' => t('State'),
      'help' => t('The name of the workflow state of the transaction.') . ' ' . t("'Counted' states only."),
      'field' => [
        'id' => 'mcapi_state',
      ],
      'filter' => [
        'id' => 'in_operator',
        'options callback' => 'mcapi_views_states',
      ],
      'sort' => [
        'id' => 'standard',
      ],
    ];

    $data['mcapi_transactions_index']['type'] = [
      'title' => t('Type'),
      'help' => t('Which form or module which created the transaction.'),
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
      'title' => t('Created'),
      'help' => t('The second the transaction was created.'),
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
    $data['mcapi_transactions_index']['exchange'] = [
      'title' => t('Current Exchange'),
      'help' => t('Any of the exchanges the current user is a member of'),
      'filter' => [
        'id' => 'mcapi_current_exchange',
      ],
    ];
    $data['mcapi_transactions_index']['created_year_month'] = [
      'title' => t('Created year + month'),
      'help' => t('Date in the form of YYYYMM.'),
      'argument' => [
        'field' => 'created',
        'id' => 'date_year_month',
      ],
    ];

    $data['mcapi_transactions_index']['created_year'] = [
      'title' => t('Created year'),
      'help' => t('Date in the form of YYYY.'),
      'argument' => [
        'field' => 'created',
        'id' => 'date_year',
      ],
    ];
    $data['mcapi_transactions_index']['incoming'] = [
      'title' => t('Income'),
      'help' => t('The income from the transaction; positive or zero'),
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
      'title' => t('Expenditure'),
      'help' => t('The outgoing quantity of the transaction; positive or zero'),
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
      'title' => t('Change in balance'),
      'help' => t('The difference to the balance; positive or negative'),
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
      'title' => t('Volume'),
      'help' => t('The quantity of the transaction; always positive'),
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
      'title' => t('Currency'),
      'help' => t('The currency'),
      'field' => [
        'id' => 'standard',
      ],
      'filter' => [
        'id' => 'standard',
      ],
      'argument' => [
        'id' => 'standard',
      ],
    ];
    $data['mcapi_transactions_index']['child'] = [
      'title' => t('Is a child'),
      'help' => t('FALSE if the transaction is the main one with that serial number'),
      'field' => [
        'id' => 'boolean',
      ],
      'filter' => [
        'id' => 'boolean',
      ],
    ];

    //virtual fields
    $data['mcapi_transactions_index']['balance'] = [
      'title' => t('Running balance'),
      'help' => t("The sum of the wallet's previous transactions."),
      'field' => [
        'id' => 'balance',
        'additional fields' => ['curr_id']
      ],
    ];
    $data['mcapi_transactions_index']['transitions'] = [
      'title' => t('Transitions'),
      'help' => t('What the user can do to the transaction'),
      'field' => [
        'id' => 'transaction_transitions',
      ]
    ];

    return $data;
  }

}

//}