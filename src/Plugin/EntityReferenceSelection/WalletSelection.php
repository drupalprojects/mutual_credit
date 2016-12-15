<?php

namespace Drupal\mcapi\Plugin\EntityReferenceSelection;

use Drupal\mcapi\Entity\Wallet;
use Drupal\Component\Utility\Html;
use Drupal\Core\Entity\Plugin\EntityReferenceSelection\DefaultSelection;

/**
 * Provide default Wallet selection handler.
 *
 * The settings consist of one value, restriction, which can be in 3 modes
 *
 * @EntityReferenceSelection(
 *   id = "default:mcapi_wallet",
 *   label = @Translation("Wallet selection"),
 *   entity_types = {"mcapi_wallet"},
 *   group = "default"
 * )
 * @todo inject the database
 * @deprecated
 */
class WalletSelection extends DefaultSelection {

  /**
   * Possible values for the restriction setting.
   */
  const RESTRICTION_PAYIN = 'payin';
  const RESTRICTION_PAYOUT = 'payout';
  const RESTRICTION_MINE = 'currentuser';
  const RESTRICTION_NONE = 'none';

  /**
   * {@inheritdoc}
   */
  public function __getReferenceableEntities($match = NULL, $match_operator = 'CONTAINS', $limit = 0) {
    $query = $this->walletsQuery($match);
    if ($limit > 0) {
      $query->range(0, $limit);
    }
    //Quicker than calling Wallet::loadMultiple, esp since entityManager is loaded
    $entities = $this->entityManager
      ->getStorage('mcapi_wallet')
      ->loadMultiple($query->execute());

    $options = ['mcapi_wallet' => []];
    foreach ($entities as $entity_id => $entity) {
      $options['mcapi_wallet'][$entity_id] = Html::escape($entity->label());
    }
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function __countReferenceableEntities($match = NULL, $match_operator = 'CONTAINS') {
    return $this->walletsQuery()->count()->execute();
  }

  /**
   * {@inheritdoc}
   */
  public function __validateReferenceableEntities(array $ids) {
    $valid_ids = $this->walletsQuery()->execute();
    // User 1 skips validation. this is helpful for importing.
    if ($this->currentUser->id() != 1) {
      $ids = array_intersect($ids, $valid_ids);
    }
    return $ids;
  }

  /**
   * Identify wallet IDs based on the string and direction.
   *
   * @param string $match
   *   A fragment of the wallet name.
   *
   * @return Drupal\Core\Entity\Query\Sql\Query
   *   An entity query object, unexecuted
   */
  public function __walletsQuery($match = NULL) {
    $settings = &$this->configuration['handler_settings'];
    $query = $this->entityManager->getStorage('mcapi_wallet')->getQuery();
    if ($match) {
      $query->condition('name', '%' . \Drupal::database()->escapeLike($match) . '%', 'LIKE');
    }
    if (isset($settings['exclude'])) {
      debug($settings['exclude'], 'settings walletSeclection exclusion setting. See TransactionForm');
      $query->condition('wid', (array)$settings['exclude'], 'IN');
    }
    switch($settings['restriction']) {
      case static::RESTRICTION_MINE:
        $query->condition('holder_entity_type', 'user');
        $query->condition('holder_entity_id', $this->currentUser->id());
        break;
    }
    return $query;
  }


  /**
   * This is only called in MassPay::buildEntity
   */
  public function inverse($ids) {
    return array_diff($this->queryEntities(), $ids);
  }
}
