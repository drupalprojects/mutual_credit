<?php

namespace Drupal\mcapi\Views;

use Drupal\views\EntityViewsData;

/**
 * Views Data for Transaction entity.
 */
class TransactionViewsData extends EntityViewsData {

  /**
   * {@inheritdoc}
   *
   * @todo see Drupal\taxonomy\TermViewsData to see how an index table can be incorporated after beta 11
   */
  public function getViewsData() {
    $data = parent::getViewsData();
    $data['mcapi_transaction']['table']['base']['defaults']['field'] = 'xid';
    $data['mcapi_transaction']['table']['wizard_id'] = 'transactions';
    $data['mcapi_transaction']['table']['access query tag'] = 'mcapi_views_access';
    // $data['mcapi_transaction']['table']['entity revision'] = NULL;.
    // Any problems with transactions see https://www.drupal.org/node/2477847
    $data['mcapi_transaction']['state']['field']['id'] = 'mcapi_state';
    $data['mcapi_transaction']['state']['filter'] = [
      'id' => 'mcapi_state',
      'options callback' => '\Drupal\mcapi\Mcapi::entityLabelList',
      'options arguments' => ['mcapi_state'],
    ];
    $data['mcapi_transaction']['type']['field']['id'] = 'mcapi_type';
    $data['mcapi_transaction']['type']['filter'] = [
      'id' => 'mcapi_type',
      'options callback' => '\Drupal\mcapi\Mcapi::entityLabelList',
      'options arguments' => ['mcapi_type'],
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

    // @todo I don't know why these relationships aren't coming automatically
    $data['mcapi_transaction']['payer']['help'] = $this->t('The wallet which was debited');
    $data['mcapi_transaction']['payer']['relationship'] = [
      'base' => 'mcapi_wallet',
      'base field' => 'wid',
      'label' => $this->t('Debited wallet'),
      'title' => $this->t('Debited wallet'),
      'id' => 'standard',
    ];
    $data['mcapi_transaction']['payer']['field']['default_formatter'] = 'entity_reference_label';

    $data['mcapi_transaction']['payee']['help'] = $this->t('The wallet which was credited.');
    $data['mcapi_transaction']['payee']['relationship'] = [
      'base' => 'mcapi_wallet',
      'base field' => 'wid',
      'label' => $this->t('Credited wallet'),
      'title' => $this->t('Credited wallet'),
      'id' => 'standard',
    ];
    $data['mcapi_transaction']['payee']['field']['default_formatter'] = 'entity_reference_label';

    //@temp
    //@see https://www.drupal.org/node/2337507
    $data['mcapi_transaction']['created']['argument'] = [
      'field' => 'created',
      'id' => 'date_fulldate',
    ];

    $data['mcapi_transactions_index']['table'] = [
      'group' => $this->t('Transaction index'),
      'provider' => 'mcapi',
      'entity type' => 'mcapi_transaction',
      'base' => [
        'field' => 'xid',
        'title' => $this->t('Transaction index'),
        'help' => $this->t("Views-friendly index of transactions in 'counted' states"),
        'access query tag' => 'mcapi_views_access',
        'weight' => 5,
        'defaults' => [],
        'cache_contexts' => $this->entityType->getListCacheContexts(),
      ],
      'entity revision' => '',
      // This links it to the wizard plugin.
      'wizard_id' => 'transaction_index',
    ];

    $data['mcapi_transactions_index']['serial'] = [
    // The item it appears as on the UI,.
      'title' => $this->t('Serial'),
      'help' => $this->t('The serial number of the transaction and dependents'),
      'field' => [
        'id' => 'standard',
      ],
      'sort' => [
        'id' => 'standard',
      ],
    ];
    $data['mcapi_transactions_index']['xid'] = [
    // The item it appears as on the UI,.
      'title' => $this->t('Transaction id'),
      'help' => $this->t('The unique database key of the transaction'),
      'field' => [
    // This might be 'transaction' if it exists.
        'id' => 'field',
      ],
      'sort' => [
        'id' => 'standard',
      ],
      'entity field' => 'xid',
    ];
    $data['mcapi_transactions_index']['wallet_id'] = [
      'title' => $this->t('Wallet ID'),
      'help' => $this->t('The wallet we are looking at'),
      'relationship' => [
        'id' => 'standard',
        'base' => 'mcapi_wallet',
        'base field' => 'wid',
        'label' => t('1st wallet'),
        'relationship field' => 'wallet_id',
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
        'label' => t('Partner wallet'),
        'relationship field' => 'partner_id',
      ],
      'field' => [
        'id' => 'standard',
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
      'help' => $this->t('The name of the workflow state of the transaction.'),
      'field' => [
        'id' => 'mcapi_state',
      ],
      'filter' => [
        'id' => 'in_operator',
        'options callback' => '\Drupal\mcapi\Mcapi::entityLabelList',
        'options arguments' => ['mcapi_state'],
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
        'options callback' => '\Drupal\mcapi\Mcapi::entityLabelList',
        'options arguments' => ['mcapi_type'],
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
        'id' => 'date',
      ],
      'filter' => [
        'id' => 'date',
      ]
    ];
    //@temp
    //@see https://www.drupal.org/node/2337507
    $data['mcapi_transactions_index']['created_fulldate'] = array(
      'title' => $this->t('Created date'),
      'help' => $this->t('Date in the form of CCYYMMDD.'),
      'argument' => array(
        'field' => 'created',
        'id' => 'date_fulldate',
      ),
    );
    $data['mcapi_transactions_index']['created_year_month'] = [
      'title' => $this->t('Created year + month'),
      'help' => $this->t('Date in the form of YYYYMM.'),
      'argument' => [
        'id' => 'date_year_month',
        'field' => 'created',
      ],
    ];

    $data['mcapi_transactions_index']['created_year'] = [
      'title' => $this->t('Created year'),
      'help' => $this->t('Date in the form of YYYY.'),
      'argument' => [
        'id' => 'date_year',
        'field' => 'created',
      ],
    ];

    $data['mcapi_transactions_index']['incoming'] = [
      'title' => $this->t('Income'),
      'help' => $this->t('The income from the transaction; positive or zero'),
      // Mimic the worth field.
      'field' => [
        'id' => 'worth',
        'additional fields' => ['curr_id'],
      ],
      'filter' => [
        // @todo make a filter that knows not to show the native value
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
        'additional fields' => ['curr_id'],
      ],
      'filter' => [
        // @todo make a filter that knows not to show the native value
        'id' => 'numeric',
      ],
      'sort' => [
        'id' => 'numeric',
      ],
    ];
    $data['mcapi_transactions_index']['diff'] = [
      'title' => $this->t('Change in balance'),
      'help' => $this->t('The difference to the balance; positive or negative.'),
      'field' => [
        'id' => 'worth',
        'additional fields' => ['curr_id'],
      ],
      'filter' => [
        'id' => 'numeric',
      ],
      'sort' => [
        'id' => 'standard',
      ],
    ];
    $data['mcapi_transactions_index']['volume'] = [
      'title' => $this->t('Volume'),
      'help' => $this->t('The quantity of the transaction; always positive'),
      'field' => [
        'id' => 'worth',
        'additional fields' => ['curr_id'],
      ],
      'filter' => [
        'id' => 'numeric',
      ],
      'sort' => [
        'id' => 'standard',
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
        'options callback' => '\Drupal\mcapi\Mcapi::entityLabelList',
        'options arguments' => ['mcapi_currency'],
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

    // Virtual fields.
    $data['mcapi_transactions_index']['running_balance'] = [
      'title' => $this->t('Running balance'),
      'help' => $this->t("The sum of the wallet's previous transactions."),
      'field' => [
        'id' => 'transaction_running_balance',
        'additional fields' => ['curr_id', 'wallet_id', 'serial', 'volume'],
      ],
    ];
    $data['mcapi_transactions_index']['first_wallet'] = [
      'title' => $this->t('First wallet of route entity'),
      'help' => $this->t("First wallet of the entity given in the route (views preview doesn't work)"),
      'argument' => [
        'id' => 'route_wallet',
        'field' => 'wallet_id'
      ]
    ];
    return $data;
  }

}
