<?php

/**
 * @file
 * Contains \Drupal\mcapi\ViewBuilder\ExchangeViewBuilder.
 */

namespace Drupal\mcapi\ViewBuilder;

use Drupal\Core\Entity\EntityViewBuilder;
use Drupal\Core\Entity\EntityInterface;

/**
 * Base class for entity view controllers.
 */
class ExchangeViewBuilder extends EntityViewBuilder {

  /**
   * {@inheritdoc}
   */
  public function getBuildDefaults(EntityInterface $entity, $view_mode, $langcode) {
    $build = parent::getBuildDefaults($entity, $view_mode, $langcode);
    //instead of using <br> here, isn't there a nicer way to make each render array into a div or something like that
    $build['people'] = array(
      '#type' => 'item',
      '#title' => t('People'),
      '#weight' => 4,
      'members' => array(
        '#prefix' => '<br />',
        '#markup' =>  l(
          t('@count members', array('@count' => $entity->members())),
          'exchange/'.$entity->id().'/members'
        )
      ),
      'admin' => array(
        '#prefix' => '<br />',
        //@todo what's the best way to show this username linked?
        '#markup' => t('Administrator: !name', array('!name' => entity_load('user', $entity->get('uid')->value)->getUsername()))
      )
    );
    //TEMP!!!
    $build['placeholder_text'] = array(
      '#weight' => -1,
    	'#markup' => 'This page needs to show some basic info about the exchange. Its members, its currencies, its admin and managers. Number of transactions ever and transaction volume per currency. TODO Check the default currency view mode'
    );
    return $build;
  }

}
