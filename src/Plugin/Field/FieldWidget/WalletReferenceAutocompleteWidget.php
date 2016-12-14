<?php

namespace Drupal\mcapi\Plugin\Field\FieldWidget;

use Drupal\Core\Field\Plugin\Field\FieldWidget\EntityReferenceAutocompleteWidget;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Symfony\Component\Validator\ConstraintViolationInterface;

/**
 * Plugin implementation of the wallet selector widget.
 *
 * @FieldWidget(
 *   id = "wallet_reference_autocomplete",
 *   label = @Translation("Wallets"),
 *   description = @Translation("Select from all wallets"),
 *   field_types = {
 *     "wallet_reference"
 *   }
 * )
 * @todo inject \Drupal::config('mcapi.settings')
 */
class WalletReferenceAutocompleteWidget extends EntityReferenceAutocompleteWidget {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return parent::defaultSettings() + [
      'max_select' => 15,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $element = parent::settingsForm($form, $form_state);
    $element['max_select'] = [
      '#title' => $this->t('Max number of wallets before select widget becomes autocomplete'),
      '#type' => 'number',
      '#default_value' => $this->getSetting('max_select'),
    ];
    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = parent::settingsSummary();
    // Size.
    //unset($summary[1]);
    $message = $this->t('Max @num items in select widget', ['@num' => $this->getSetting('max_select') ]);
    $summary['hide_one'] = ['#markup' => $message];
    return $summary;
  }

  /**
   * {@inheritdoc}
   *
   * @see mcapi_field_widget_wallet_reference_autocomplete_form_alter
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $referenced_entities = $items->referencedEntities();
    $default_value_wallet = isset($referenced_entities[$delta]) ? $referenced_entities[$delta] : NULL;
    if ($default_value_wallet && $default_value_wallet->isIntertrading()) {
      $wid_options = [$default_value_wallet->id() => $default_value_wallet->label()];
    }
    else {
      // Get wallets the current user is permitted to pay in/out of
      $entity_ids = \Drupal::service('plugin.manager.entity_reference_selection')
        ->getSelectionHandler($this->fieldDefinition)
        ->getReferenceableEntities(NULL, 'contains');
      $wid_options = $entity_ids['mcapi_wallet'];
    }
    $count = count($wid_options);

    if (!$count) {
      drupal_set_message($this->t('No wallets to show for @role', ['@role' => $this->fieldDefinition->getLabel()]), 'error');
      $form['#disabled'] = TRUE;
      return [];
    }
    // Present different widgets according to the number of wallets to choose
    // from, and settings.
    if ($count < $this->getSetting('max_select')) {
      $element += [
        '#type' => 'select',
        '#options' => $wid_options,
        '#default_value' => $default_value_wallet ? $default_value_wallet->id() : '',
      ];
    }
    else {
      $element += [
        '#type' => 'wallet_entity_auto', //this is just a wrapper around element entity_autocomplete
        //'#selection_settings' => ['restriction' => $restriction],
        '#selection_settings' => $this->fieldDefinition->getSetting('handler_settings'),
        '#default_value' => $default_value_wallet,
        '#placeholder' => $this->getSetting('placeholder'),
      ];
    }
    return ['target_id' => $element];
  }

  /**
   * {@inheritdoc}
   */
  public function errorElement(array $element, ConstraintViolationInterface $error, array $form, FormStateInterface $form_state) {
    return isset($element['target_id']) ? $element['target_id'] : FALSE;
  }

  /**
   * @todo
   * @see src/Plugin/EntityReferenceSelection/WalletSelection.php
   */
  function inverse() {
    die('called WalletReferenceAutocompleteWidget::inverse');
  }

}
