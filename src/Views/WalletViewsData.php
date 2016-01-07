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

    $data['mcapi_wallet']['name']['help'] = $this->t("When the holder can hold one wallet only, the wallet's name is inherit from its holder");
    //@todo surely the entity label should have been added already?
    $data['mcapi_wallet']['label'] = [
      'title' => t('Label'),
      'help' => t('The label of the wallet, usually the same as the name'),
      'real field' => 'wid',
      'field' => [
        //@todo waiting for entity_label to abandon the 'entity type field' in its definition
        'id' => 'wallet_label',
        'entity type field' => 'holder_entity_type',
      ],
    ];

    $data['mcapi_wallet']['holder'] = [
      'title' => t('Link to holding entity'),
      'help' => t('Could be any entity implementing OwnerInterface'),
      'field' => [
        'id' => 'mcapi_wallet_holder',
      ],
    ];
    $data['mcapi_wallet']['holder_entity_type']['field']['id'] = 'mcapi_holder_type';
    $data['mcapi_wallet']['holder_entity_type']['field']['help'] = $this->t("The wallet holder's translated EntityType name");
    
    
    return $data;
  }
}
