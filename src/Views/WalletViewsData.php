<?php

/**
 * @file
 * Contains \Drupal\mcapi\Views\WalletViewsData.
 *
 */
namespace Drupal\mcapi\Views;

use Drupal\views\EntityViewsData;

class WalletViewsData extends EntityViewsData {

  /**
   * {@inheritdoc}
   */
  public function getViewsData() {
    $data = parent::getViewsData();
    $data['mcapi_wallet']['wid']['field']['id'] = 'mcapi_entity';
    unset($data['mcapi_wallet']['pid']);

    $data['mcapi_wallet']['table']['base']['defaults']['field'] = 'wallet_label';

    $data['mcapi_wallet']['wallet_label'] = [
      'title' => t('Label'),
      'help' => t('the name of the wallet'),
      'field' => [
        'id' => 'mcapi_wallet_label',
      ]
    ];
    $data['mcapi_wallet']['holder'] = [
      'title' => t('The wallet holder'),
      'field' => [
        'id' => 'mcapi_wallet_holder',
      ],
    ];
    $data['mcapi_wallet']['entity_type']['field']['id'] = 'mcapi_holder_type';

    return $data;
  }
}
