<?php

namespace Drupal\mcapi\Plugin\Block;

use Drupal\mcapi\Exchange;
use Drupal\mcapi\Entity\Currency;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Displays all the wallets of an entity.
 *
 * Entity being either the current user OR the entity being viewed. Shows the
 * wallet view mode 'mini'.
 *
 * @Block(
 *   id = "currency_summary",
 *   admin_label = @Translation("Currency summary"),
 *   category = @Translation("Community Accounting")
 * )
 */
class Currencies extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManager $entity_type_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * Injection.
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $currencies = Exchange::currenciesAvailableToUser();
    return $this->entityTypeManager
      ->getViewBuilder('mcapi_currency')
      ->viewMultiple($currencies, 'summary');
  }

}
