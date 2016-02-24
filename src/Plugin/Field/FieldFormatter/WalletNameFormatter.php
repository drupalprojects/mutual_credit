<?php

/**
 * @file
 * Contains \Drupal\Core\Field\Plugin\Field\FieldFormatter\WalletNameFormatter.
 *
 * @deprecated remove all methods but not the file
 */

namespace Drupal\mcapi\Plugin\Field\FieldFormatter;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Field\FieldItemListInterface;

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
class WalletNameFormatter extends \Drupal\Core\Field\Plugin\Field\FieldFormatter\EntityReferenceLabelFormatter {

  /**
   * {@inheritdoc}
   *
   * Wallet names can always be seen. Usually this is in the context of viewing
   * a transaction which has its own more detailed access control
   */
  protected function checkAccess(EntityInterface $entity) {
    return \Drupal\Core\Access\AccessResult::allowed();
  }

}

