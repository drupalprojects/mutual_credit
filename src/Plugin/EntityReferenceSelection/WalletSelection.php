<?php

/**
 * @file
 * Contains \Drupal\mcapi\Plugin\EntityReferenceSelection\WalletSelection.
 */

namespace Drupal\mcapi\Plugin\EntityReferenceSelection;

/**
 * Provide default Wallet selection handler.
 *
 * @EntityReferenceSelection(
 *   id = "default:mcapi_wallet",
 *   label = @Translation("Wallet selection"),
 *   entity_types = {"mcapi_wallet"},
 *   group = "default"
 * )
 */
class WalletSelection extends \Drupal\Core\Entity\Plugin\EntityReferenceSelection\DefaultSelection {

  /**
   *
   * @param type $match
   * @param type $match_operator IS IGNORED
   * @param type $limit
   */
  public function getReferenceableEntities($match = NULL, $match_operator = 'CONTAINS', $limit = 0) {
    $wids = $this->queryEntities($match);
    if ($limit > 0) {
      $wids = array_splice($wids, 0, $limit);
    }
    $entities = $this->entityManager
      ->getStorage('mcapi_wallet')
      ->loadMultiple($wids);

    $options = [];
    foreach ($entities as $entity_id => $entity) {
      $options['mcapi_wallet'][$entity_id] = \Drupal\Component\Utility\Html::escape($entity->label());
    }
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function countReferenceableEntities($match = NULL, $match_operator = 'CONTAINS') {
    $wids = $this->queryEntities();
    return count($wids);
  }

  /**
   * {@inheritdoc}
   */
  public function validateReferenceableEntities(array $ids) {
    //user 1 skips validation. this is helpful for importing
    if (\Drupal::currentUser()->id() != 1) {
      $ids = array_intersect($ids, $this->queryEntities());
    }
    return $ids;
  }

  /**
   *
   * @param type $match
   * @param type $match_operator
   *
   * @return integer[]
   *   wallet ids
   */
  private function queryEntities($match = NULL) {
    //print_r($this->configuration);die();
    return \Drupal::entityTypeManager()->getStorage('mcapi_wallet')
      ->whichWalletsQuery(
        $this->configuration['handler_settings']['op'],
        \Drupal::currentUser()->id(),
        $match
      );
  }


}

