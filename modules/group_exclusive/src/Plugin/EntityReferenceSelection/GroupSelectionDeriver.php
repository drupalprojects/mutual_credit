<?php

namespace Drupal\group_exclusive\Plugin\EntityReferenceSelection;

use Drupal\Component\Plugin\Derivative\DeriverBase;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Plugin\Discovery\ContainerDeriverInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides derivative plugins for the GroupSelection plugin.
 *
 * @note This is parked here for matslats' convenience. Hopefully this will be
 * included in the group module. Referenced by ExclusiveGroupSelection
 */
class GroupSelectionDeriver extends DeriverBase implements ContainerDeriverInterface {

  /**
   * The entity manager
   *
   * @var \Drupal\Core\Entity\EntityManagerInterface
   */
  protected $entityManager;

  /**
   * Creates an SelectionBase object.
   *
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The entity manager.
   */
  public function __construct(EntityManagerInterface $entity_manager) {
    $this->entityManager = $entity_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, $base_plugin_id) {
    return new static(
      $container->get('entity.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    foreach ($this->entityManager->getDefinitions() as $entity_type_id => $entity_type) {
      $this->derivatives[$entity_type_id] = $base_plugin_definition;
      $this->derivatives[$entity_type_id]['entity_types'] = [$entity_type_id];
      $this->derivatives[$entity_type_id]['label'] = t('@entity_type selection', ['@entity_type' => $entity_type->getLabel()]);
      $this->derivatives[$entity_type_id]['base_plugin_label'] = (string) $base_plugin_definition['label'];

      if (!$entity_type->hasKey('label')) {
        // @see Drupal\Core\Entity\Plugin\Derivative\DefaultSelectionDeriver::getDerivativeDefinitions
        $this->derivatives[$entity_type_id]['class'] = 'Drupal\Core\Entity\Plugin\EntityReferenceSelection\PhpSelection';
      }
    }
    return parent::getDerivativeDefinitions($base_plugin_definition);
  }

}
