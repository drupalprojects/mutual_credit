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
  public function getBuildDefaults(EntityInterface $entity, $view_mode) {
    $build = parent::getBuildDefaults($entity, $view_mode);

    $build['intertrading'] = [
      '#type' => 'link',
      '#title' => t('Intertrading wallet'),
      '#weight' => 5,
      '#url' => Url::fromRoute('entity.mcapi_wallet.canonical', ['mcapi_wallet' => $entity->intertradingWallet()->id()])
    ];


    //TEMP!!!
    $build['placeholder_text'] = [
      '#weight' => -1,
      '#markup' => 'This page needs to show some basic info about the exchange. Its members, its currencies, its admin and managers. Number of transactions ever and transaction volume per currency. Probably this page should have its own twig template. TODO Check the default currency view mode. '
    ];
    return $build;
  }

}
