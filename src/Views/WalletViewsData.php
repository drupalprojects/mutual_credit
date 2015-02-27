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

    $data['mcapi_wallet']['table'] = array(
      'group'  => t('Wallets'),
      'entity type' => 'mcapi_wallet',
      'base' => array(
        'field' => 'wid',
        'title' => t('Wallets'),
        'help' => t('List of wallets'),
        'weight' => 5,
        'defaults' => array(
          'field' => 'wallet_label',
        )
      ),
    );
    $data['mcapi_wallet']['wid'] = array(
      'title' => t('Wallet ID'),
      'help' => t('the unique id of the wallet'),
      'field' => array(
        'id' => 'mcapi_entity'
      ),
      'argument' => array(
        'id' => 'standard',
      )
    );
    $data['mcapi_wallet']['wallet_label'] = array(
      'title' => t('Label'),
      'help' => t('the name of the wallet'),
      'field' => array(
        'id' => 'mcapi_wallet_label',
      )
    );
    $data['mcapi_wallet']['owner'] = array(
      'title' => t('The wallet owner'),
      'field' => array(
        'id' => 'mcapi_wallet_owner',
      ),
    );
    $data['mcapi_wallet']['entity_type'] = array(
      'title' => t('Owner type'),
      'help' => t('The entity type of the wallet owner. good for grouping by'),
      'field' => array(
        'id' => 'mcapi_owner_type',
      ),
      'filter' => array(
        'id' => 'standard',
      )
    );
    return $data;
  }
}
