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


    //unwanted fields
    unset($data['mcapi_wallet']['pid']);
    unset($data['mcapi_wallet']['payin']);
    unset($data['mcapi_wallet']['payout']);
    unset($data['mcapi_wallet']['details']);
    unset($data['mcapi_wallet']['summary']);
    unset($data['mcapi_wallet']['operations']);
    
    //@todo surely the entity label should have been added already?
    $data['mcapi_wallet']['label'] = [
      'title' => t('Label'),
      'help' => t('The label of the wallet'),
      'real field' => 'wid',
      'field' => [
        //@todo waiting for entity_label to abandon the 'entity type field' in its definition
        'id' => 'wallet_label',
        'entity type field' => 'entity_type',
      ],
    ];

    $data['mcapi_wallet']['holder'] = [
      'title' => t('Holding entity'),
      'help' => t('Could be any entity implementing OwnerInterface'),
      'field' => [
        'id' => 'mcapi_wallet_holder',
      ],
    ];
    $data['mcapi_wallet']['owner'] = [
      'title' => t('Owner user'),
      'help' => t('The user who is ultimately responsible for the wallet'),
      'field' => [
        'id' => 'mcapi_wallet_user',
      ]
    ];
    $data['mcapi_wallet']['entity_type']['field']['id'] = 'mcapi_holder_type';
    $data['mcapi_wallet']['entity_type']['field']['help'] = $this->t("The wallet holder's translated EntityType name");

    return $data;
  }
}
