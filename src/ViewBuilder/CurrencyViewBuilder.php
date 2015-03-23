<?php

/**
 * @file
 * Contains \Drupal\mcapi\ViewBuilder\CurrencyViewBuilder.
 */

namespace Drupal\mcapi\ViewBuilder;

use Drupal\Core\Entity\EntityViewBuilder;
use Drupal\Core\Entity\EntitytypeInterface;

/**
 * NB EntityViewBuilder should have been called ContentEntityViewBuilder
 */
class CurrencyViewBuilder extends EntityViewBuilder {

  public function __construct(EntityTypeInterface $entity_type, EntityManagerInterface $entity_manager, LanguageManagerInterface $language_manager) {
    $this->entityTypeId = 'mcapi_currency';//$ntity_type->id() doesn't work on config entities
    $this->entityType = $entity_type;
    $this->entityManager = $entity_manager;
    $this->languageManager = $language_manager;
  }
  
  /**
   * {@inheritdoc}
   * 
   */
  public function build(array $build) {
    //there's nothing to build we can just theme it as is
    return $build;
  }

}
