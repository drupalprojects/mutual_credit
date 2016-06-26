<?php

namespace Drupal\mcapi\Plugin\EntityReferenceSelection;

use Drupal\mcapi\Mcapi;
use Drupal\Component\Utility\Html;
use Drupal\Core\Entity\Plugin\EntityReferenceSelection\DefaultSelection;

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
class WalletSelection extends DefaultSelection {

  /**
   * {@inheritdoc}
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
      $options['mcapi_wallet'][$entity_id] = Html::escape($entity->label());
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
    // User 1 skips validation. this is helpful for importing.
    if (\Drupal::currentUser()->id() != 1) {
      $ids = array_intersect($ids, $this->queryEntities());
    }
    return $ids;
  }

  /**
   * Identify wallet IDs based on the string and direction.
   *
   * @param string $match
   *   A fragment of the wallet name.
   *
   * @return integer[]
   *   wallet ids
   */
  private function queryEntities($match = NULL) {
    return Mcapi::getWalletSelection(
      $match,
      $this->configuration['handler_settings']['direction']
    );
  }

}
