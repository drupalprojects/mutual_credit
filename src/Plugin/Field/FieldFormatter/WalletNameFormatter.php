<?php

namespace Drupal\mcapi\Plugin\Field\FieldFormatter;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Field\Plugin\Field\FieldFormatter\EntityReferenceLabelFormatter;
use Drupal\Core\Entity\EntityInterface;

/**
 * Plugin implementation of the 'wallet_name' formatter.
 *
 * @FieldFormatter(
 *   id = "wallet_name",
 *   label = @Translation("wallet name"),
 *   field_types = {
 *     "wallet_reference"
 *   }
 * )
 */
class WalletNameFormatter extends EntityReferenceLabelFormatter {

  /**
   * {@inheritdoc}
   *
   * Wallet names can always be seen. Usually this is in the context of viewing
   * a transaction which has its own more detailed access control.
   */
  protected function checkAccess(EntityInterface $entity) {
    return AccessResult::allowed();
  }

}
