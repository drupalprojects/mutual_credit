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
 *   id = "default:wallet",
 *   label = @Translation("Wallet selection"),
 *   group = "mcapi"
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
    echo 'getReferenceableEntities'; print_R($wids);
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
    return array_intersect($ids, $this->queryEntities());
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
    return \Drupal::entityTypeManager()->getStorage('mcapi_wallet')
      ->whichWalletsQuery(
        $this->configuration['handler_settings']['op'],
        \Drupal::currentUser()->id(),
        $match
      );
  }


  /**
   * Builds an EntityQuery to get referenceable entities.
   *
   * @param string|null $match
   *   (Optional) Text to match the label against. Defaults to NULL.
   * @param string $match_operator
   *   (Optional) The operation the matching should be done with. Defaults
   *   to "CONTAINS".
   *
   * @return \Drupal\Core\Entity\Query\QueryInterface
   *   The EntityQuery object with the basic conditions and sorting applied to
   *   it.
   */
  protected function buildEntityQuery($match = NULL, $match_operator = 'CONTAINS') {

    mtrace();//this should NEVER be called

  }

}
