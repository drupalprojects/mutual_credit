<?php

/**
 * @file
 * Contains \Drupal\Core\Field\Plugin\Field\FieldFormatter\WalletNameFormatter.
 *
 * @deprecated remove all methods but not the file
 */

namespace Drupal\mcapi\Plugin\Field\FieldFormatter;

use Drupal\Core\Entity\EntityInterface;

/**
 * Plugin implementation of the 'worth' formatter.
 *
 * @FieldFormatter(
 *   id = "wallet_name",
 *   label = @Translation("wallet name"),
 *   field_types = {
 *     "wallet"
 *   }
 * )
 */
class WalletNameFormatter extends \Drupal\Core\Field\Plugin\Field\FieldFormatter\EntityReferenceLabelFormatter {

  /**
   * {@inheritdoc}
   *
   * Grants everyone with access to see the main entity permission to view the
   * wallet name, if not the wallet. By granting access here, a link is created
   * to the destination wallet canonical page, even if the user can't see that page.
   */
  protected function checkAccess(EntityInterface $entity) {
    return parent::checkAccess($entity);
    return \Drupal\Core\Access\AccessResult::allowed()->cachePerPermissions('view user profile');
  }
}

