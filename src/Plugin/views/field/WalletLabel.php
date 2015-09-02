<?php

/**
 * @file
 * Definition of Drupal\mcapi\Plugin\views\field\WalletLabel.
 * 
 * @note this is temporary
 */

namespace Drupal\mcapi\Plugin\views\field;

use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\Plugin\views\display\DisplayPluginBase;
use Drupal\views\ResultRow;
use Drupal\views\ViewExecutable;
use Drupal\views\Plugin\views\field\EntityLabel;
use Drupal\mcapi\Entity\Wallet;

/**
 * Field handler for the name of the transaction state
 * I would hope for a generic filter to come along to render list key/values
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("wallet_label")
 */
class WalletLabel extends EntityLabel {


  public function preRender(&$values) {
    
  }
  public function render(ResultRow $values) {
    $entity = Wallet::load( $this->getValue($values));
    
    if (!empty($this->options['link_to_entity'])) {
      $this->options['alter']['make_link'] = TRUE;
      $this->options['alter']['url'] = $entity->urlInfo();
    }

    return $this->sanitizeValue($entity->label());
  }

}
