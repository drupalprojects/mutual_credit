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

    $data['mcapi_wallet']['name']['help'] = $this->t("When the holder can hold one wallet only, the wallet inherits the name of its holder");
    //@todo surely the entity label should have been added already?
    $data['mcapi_wallet']['label'] = [
      'title' => $this->t('Label'),
      'help' => $this->t('The label of the wallet, (usually the same as the raw name)'),
      'real field' => 'wid',
      'field' => [
        //@todo waiting for entity_label to abandon the 'entity type field' in its definition
        'id' => 'entity_label',
        'entity type field' => 'holder_entity_type',
      ],
    ];

    $data['mcapi_wallet']['holder'] = [
      'title' => $this->t('Link to holding entity'),
      'help' => $this->t('Could be any entity implementing OwnerInterface'),
      'field' => [
        'id' => 'mcapi_wallet_holder',
      ],
    ];
    $data['mcapi_wallet']['holder_entity_type']['field']['id'] = 'mcapi_holder_type';
    $data['mcapi_wallet']['holder_entity_type']['field']['help'] = $this->t("The wallet holder's translated EntityType name");

    $this->addWalletSummaries($data['mcapi_wallet']);

    //other stats are available, total expenditure, total income, number of trading partners.

    return $data;
  }

  static function addWalletSummaries(array &$table) {
    $table['summary_balance'] = [
      'title' => t('Wallet current balance'),
      'field' => [
        'id' =>'wallet_summary',
        'stat' => 'balance'
      ]
    ];
    $table['summary_trades'] = [
      'title' => t('Wallet transaction count'),
      'field' => [
        'id' =>'wallet_summary',
        'stat' => 'trades'
      ]
    ];
    $table['summary_volume'] = [
      'title' => t('Wallet trading volume'),
      'field' => [
        'id' =>'wallet_summary',
        'stat' => 'volume'
      ]
    ];
  }
}

