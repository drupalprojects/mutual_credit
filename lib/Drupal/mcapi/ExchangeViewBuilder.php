<?php

/**
 * @file
 * Contains \Drupal\mcapi\ExchangeViewBuilder.
 */

namespace Drupal\mcapi;

use Drupal\Core\Entity\EntityViewBuilder;

/**
 * Base class for entity view controllers.
 */
class ExchangeViewBuilder extends EntityViewBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildContent(array $entities, array $displays, $view_mode, $langcode = NULL) {
    parent::buildContent($entities, $displays, $view_mode, $langcode);
    foreach ($entities as $exchange) {

      if ($roles = user_role_names(TRUE, 'exchange helper')) {
        //get all the helper users in this exchange
        $query = db_select('users_roles', 'ur')->fields('ur', array('uid'));
        $query->join('user__field_exchanges', 'f', 'f.entity_id = ur.uid');
        $query->condition('ur.rid', array_keys($roles));

        $helpers = $query->condition('f.field_exchanges_target_id', $exchange->id())
          ->execute()->fetchCol();

        foreach (entity_load_multiple('user', $helpers) as $account) {
          $helpernames[] = theme('username', array('account' => $account));
        }
      }
      if (empty($helpernames)) $helpernames = array(t('None'));

      //instead of using <br> here, isn't there a nicer way to make each render array into a div or something like that
      $exchange->content['people'] = array(
        '#type' => 'item',
        '#title' => t('People'),
        '#weight' => 4,
        'members' => array(
          '#prefix' => '<br />',
          '#markup' =>  l(t('@count members', array('@count' => $exchange->members())), 'members/'.$exchange->id())
        ),
        'admin' => array(
          '#prefix' => '<br />',
          //@todo what's the best way to show this username linked?
          '#markup' => t('Administrator: !name', array('!name' => entity_load('user', $exchange->get('uid')->value)->getUsername()))
        ),
        'helpers' => array(
          '#prefix' => '<br />',
          '#markup' => t('Helpers: !names', array('!names' => implode(', ', $helpernames)))
        )
      );
      $exchange->content['placeholder_text'] = array(
        '#weight' => -1,
      	'#markup' => 'This page needs to show some basic info about the exchange. Its members, its currencies, its admin and managers. Number of transactions ever and transaction volume per currency.'
      );
    }
  }

}
