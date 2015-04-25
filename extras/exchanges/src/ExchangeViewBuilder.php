<?php

/**
 * @file
 * Contains \Drupal\mcapi_exchanges\ExchangeViewBuilder.
 */

namespace Drupal\mcapi_exchanges;

use Drupal\Core\Entity\EntityViewBuilder;
use Drupal\Core\Entity\EntityInterface;
use Drupal\user\Entity\User;
use Drupal\Core\Template\Attribute;
use Drupal\Core\Url;

/**
 * Base class for entity view controllers.
 */
class ExchangeViewBuilder extends EntityViewBuilder {

  /**
   * {@inheritdoc}
   */
  public function getBuildDefaults(EntityInterface $entity, $view_mode, $langcode) {
    $build = parent::getBuildDefaults($entity, $view_mode, $langcode);
    //we shouldn't have to build all of this stuff until the entity display has been processed
    //instead of using <br> here, isn't there a nicer way to make each render array into a div or something like that
    $link = Url::fromUri('base://exchange/' . $entity->id() . '/members');
    $build['people'] = array(
      '#type' => 'item',
      '#title' => t('People'),
      '#weight' => 4,
      'members' => array(
        '#prefix' => '<br />',
        '#markup' => \Drupal::l(
          t('@count members', array('@count' => count($entity->users()))), Url::fromUri('base://exchange/' . $entity->id() . '/members')//this is a view
        )
      ),
      'admin' => array(
        '#prefix' => '<br />',
        //@todo what's the best way to show this username linked?
        '#markup' => t('Administrator: !name', array('!name' => $entity->get('uid')->entity->getUsername()))
      )
    );
    //TODO how do we allow any display or extra_field of the wallet to be viewed as a field in the exchange?
    //in extra_fields, no options are possible at the moment
    $build['intertrading'] = array(
      '#type' => 'item',
      '#title' => t('Intertrading wallet'),
      '#weight' => 5,
      '#markup' => \Drupal::l(
        t('View'), Url::fromRoute('entity.mcapi_wallet.canonical', array('mcapi_wallet' => $entity->intertrading_wallet()->id()))
      )
    );


    $count = db_select("user__exchanges", 'e')->fields('e', array('entity_id'))
        ->condition('exchanges_target_id', $entity->id())
        ->countQuery()->execute()->fetchField();
    $build['members_link'] = array(
      '#type' => 'item',
      '#title' => t('@count members', array('@count' => $count)),
      'link' => array(
        '#type' => 'link',
        '#title' => t('Show members'),
        '#href' => 'exchange/' . $entity->id() . '/members',
        //maybe instead put a view with the last five members here
        '#options' => array(
          'attributes' => new Attribute(array('title' => "This view doesn't exist yet"))
        )
      )
    );

    $build['transactions_link'] = array(
      '#type' => 'item',
      '#title' => t('@count transactions', array('@count' => $entity->transactions())),
      'link' => array(
        '#type' => 'link',
        '#title' => t('Show transactions'),
        '#href' => 'exchange/' . $entity->id() . '/transactions',
        //TODO maybe instead put a view with the last five members here
        '#options' => array(
          'attributes' => new Attribute(array('title' => "This view doesn't exist yet"))
        )
      )
    );

    //TEMP!!!
    $build['placeholder_text'] = array(
      '#weight' => -1,
      '#markup' => 'This page needs to show some basic info about the exchange. Its members, its currencies, its admin and managers. Number of transactions ever and transaction volume per currency. Probably this page should have its own twig template. TODO Check the default currency view mode. '
    );
    return $build;
  }

}
