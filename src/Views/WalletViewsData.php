<?php

/**
 * @file
 * Contains \Drupal\mcapi\Views\WalletViewsData.
 *
 */

namespace Drupal\mcapi\Views;

use Drupal\views\EntityViewsDataInterface;

class WalletViewsData implements EntityViewsDataInterface {

  /**
   * {@inheritdoc}
   */
  public function getViewsData() {
    $data = [];

    $data['mcapi_wallet']['table'] = [
      'group' => t('Wallets'),
      'entity type' => 'mcapi_wallet',
      'base' => [
        'field' => 'wid',
        'title' => t('Wallets'),
        'help' => t('List of wallets'),
        'weight' => 5,
        'defaults' => [
          'field' => 'wallet_label',
        ]
      ],
      'entity revision' => ''//temp
    ];
    $data['mcapi_wallet']['wid'] = [
      'title' => t('Wallet ID'),
      'help' => t('the unique id of the wallet'),
      'field' => [
        'id' => 'mcapi_entity'
      ],
      'argument' => [
        'id' => 'standard',
      ]
    ];
    $data['mcapi_wallet']['wallet_label'] = [
      'title' => t('Label'),
      'help' => t('the name of the wallet'),
      'field' => [
        'id' => 'mcapi_wallet_label',
      ]
    ];
    $data['mcapi_wallet']['owner'] = [
      'title' => t('The wallet owner'),
      'field' => [
        'id' => 'mcapi_wallet_owner',
      ],
    ];
    $data['mcapi_wallet']['entity_type'] = [
      'title' => t('Owner type'),
      'help' => t('The entity type of the wallet owner. good for grouping by'),
      'field' => [
        'id' => 'mcapi_owner_type',
      ],
      'filter' => [
        'id' => 'standard',
      ]
    ];
    return $data;
  }

}
