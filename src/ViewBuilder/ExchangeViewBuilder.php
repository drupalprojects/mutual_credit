<?php

/**
 * @file
 * Contains \Drupal\mcapi\ViewBuilder\ExchangeViewBuilder.
 */

namespace Drupal\mcapi\ViewBuilder;

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
      //instead of using <br> here, isn't there a nicer way to make each render array into a div or something like that
      $exchange->content['people'] = array(
        '#type' => 'item',
        '#title' => t('People'),
        '#weight' => 4,
        'members' => array(
          '#prefix' => '<br />',
          '#markup' =>  l(
            t('@count members', array('@count' => $exchange->members())),
            'exchange/'.$exchange->id().'/members'
          )
        ),
        'admin' => array(
          '#prefix' => '<br />',
          //@todo what's the best way to show this username linked?
          '#markup' => t('Administrator: !name', array('!name' => entity_load('user', $exchange->get('uid')->value)->getUsername()))
        )
      );
      //TEMP!!!
      $exchange->content['placeholder_text'] = array(
        '#weight' => -1,
      	'#markup' => 'This page needs to show some basic info about the exchange. Its members, its currencies, its admin and managers. Number of transactions ever and transaction volume per currency. TODO Check the default currency view mode'
      );
    }
  }

}
