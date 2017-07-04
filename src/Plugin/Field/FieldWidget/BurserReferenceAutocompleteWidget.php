<?php

namespace Drupal\mcapi\Plugin\Field\FieldWidget;

use Drupal\Core\Field\Plugin\Field\FieldWidget\EntityReferenceAutocompleteWidget;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Field\FieldItemListInterface;

/**
 * Plugin implementation of the wallet selector widget.
 *
 * @FieldWidget(
 *   id = "burser_reference_autocomplete",
 *   label = @Translation("Bursers"),
 *   description = @Translation("Users who can control this wallet"),
 *   field_types = {
 *     "burser_reference"
 *   }
 * )
 */
class BurserReferenceAutocompleteWidget extends EntityReferenceAutocompleteWidget {

  /**
   * {@inheritdoc}
   */
  protected function formMultipleElements(FieldItemListInterface $items, array &$form, FormStateInterface $form_state) {
    $elements['#attached']['library'][] = 'mcapi/mcapi.wallets';
    return parent::formMultipleElements($items, $form, $form_state);
  }

}

