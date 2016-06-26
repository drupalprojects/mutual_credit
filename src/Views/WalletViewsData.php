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

    $data['mcapi_wallet']['holder'] = [
      'title' => $this->t('Link to holding entity'),
      'help' => $this->t('Could be any entity implementing OwnerInterface'),
      'field' => [
        'id' => 'mcapi_wallet_holder',
      ],
    ];

    $data['mcapi_wallet']['holder_entity_type']['field']['id'] = 'mcapi_holder_type';
    $data['mcapi_wallet']['holder_entity_type']['field']['help'] = $this->t("The wallet holder's translated EntityType name");

    // This allows us to do group queries such as find the balance per wallet.
    $data['mcapi_transactions_index']['table']['join'] = [
      'mcapi_wallet' => [
        'left_field' => 'wid',
        'field' => 'wallet_id',
        'required' => TRUE,
        // 'type' => 'RIGHT'.
      ],
    ];
    $this->addWalletSummaries($data['mcapi_wallet']);

    $data['users_field_data']['first_wid']['relationship'] = [
      'title' => $this->t("The user's first wallet"),
      'help' => $this->t("Reference the first wallet each user. N.B. Reqires aggregate query"),
      'base' => 'mcapi_wallet',
      'base field' => 'wid',
      'field' => 'uid',
      'label' => $this->t('First wallet'),
      'id' => 'standard',
    ];

    return $data;
  }

  /**
   * This is added to all walletable entity types.
   */
  public static function addWalletSummaries(array &$table) {
    $table['summary_balance'] = [
      'title' => t('Current balance'),
      'description' => t("Balances of entity's first wallet"),
      'field' => [
        'id' => 'wallet_stat',
        'stat' => 'balance',
      ],
    ];
    $table['summary_trades'] = [
      'title' => t('Transaction count'),
      'description' => t("Number of trades in entity's first wallet"),
      'field' => [
        'id' => 'wallet_stat',
        'stat' => 'trades',
      ],
    ];
    $table['summary_volume'] = [
      'title' => t('Trading volume'),
      'description' => t("Volumes entity's first wallet"),
      'field' => [
        'id' => 'wallet_stat',
        'stat' => 'volume',
      ],
    ];
  }

}
