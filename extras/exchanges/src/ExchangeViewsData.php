<?php

/**
 * @file
 * Contains \Drupal\mcapi_exchanges\ExchangeViewsData.
 *
 */

namespace Drupal\mcapi_exchanges;

use Drupal\views\EntityViewsDataInterface;


class ExchangeViewsData implements EntityViewsDataInterface {

  /**
   * {@inheritdoc}
   */
  public function getViewsData() {
    $data = [];

    $data['mcapi_exchange']['table'] = array(
      'group'  => t('Exchanges'),
      'entity type' => 'mcapi_exchange',
      'base' => array(
        'field' => 'id',
        'title' => t('Exchanges'),
        'help' => t('List of Exchanges'),
        'weight' => 5,
        'defaults' => array(
          'field' => 'entity_label',
        )
      ),
    );
    $data['mcapi_exchange']['id'] = array(
      'title' => t('Unique id'), // The item it appears as on the UI,
      'help' => t('The unique database key of the exchange'),
      'field' => array(
        'id' => 'mcapi_entity',
      ),
    );

    $data['mcapi_exchange']['name'] = array(
      'title' => t('Name'),
      'help' => t('The name of the exchange'),
      'field' => array(
        //for now we'll make a link by rewriting the field.
        //But surely I don't have to create a field handler just to link to the entity
        //views/field/EntityLabel.php is so useless - only works with the file entity.
        'id' => 'standard',
      )
    );

    $data['mcapi_exchange']['uid'] = array(
      'title'=> t('Manager'),
      'help' => t('Manager of the exchange'),
      'relationship' => array(
        'title' => t('Exchange manager'),
        'help' => t('The user who can edit the exchange itself'),
        'id' => 'standard',
        'base' => 'users',
        'field' => 'uid',
        'label' => t('manager'),
      ),
      'filter' => array(
        'id' => 'user_name',
      ),
      'argument' => array(
        'id' => 'numeric',
      ),
      'field' => array(
        'id' => 'user',
      )
    );
    $data['mcapi_exchange']['status'] = array(
      'title' => t('Active'),
      'help' => t("Active or disabled"),
      'field' => array(
        'id' => 'boolean',
      ),
      'filter' => array(
        'id' => 'boolean_operator',
      )
    );
    $data['mcapi_exchange']['visibility'] = array(
      'title' => t('Visibility'),
      'help' => t("See, private, restricted and public exchanges, according to current user's access"),
      //TODO can't we use booleans for this?
      'field' => array(
        'id' => 'exchange_visibility',
      ),
      'filter' => array(
        'id' => 'exchange_visibility',
      )
    );
    $data['mcapi_exchange']['open'] = array(
      'title' => t('Open'),
      'help' => t("Whether the exchange is open to outside trade"),
      'field' => array(
        'id' => 'boolean',
      ),
      'filter' => array(
        'id' => 'boolean_operator',
      )
    );

    //virtual fields
    $data['mcapi_exchange']['membership'] = array(
      'title' => t('Member count'),
      'help' => t("The number of 'active' users in the exchange"),
      'field' => array(
        'id' => 'exchange_members',
        'additional fields' => array('id')
      )
    );
    // this might not be worth it, especially since we can't sort with it
    //if we wanted to sort we would
    //make a GROUP view on transaction_index and make a relationship to exchanges
    $data['mcapi_exchange']['transactions'] = array(
      'title' => t('Transaction count'),
      'help' => t('The number of transactions in a given period'),
      'field' => array(
        'id' => 'exchange_transactions',
        'additional fields' => array(
          'id'
        )
      )
    );
    return $data;
  }
}