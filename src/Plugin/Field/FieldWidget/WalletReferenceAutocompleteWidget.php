<?php

namespace Drupal\mcapi\Plugin\Field\FieldWidget;

use Drupal\mcapi\Entity\Wallet;
use Drupal\Core\Field\Plugin\Field\FieldWidget\EntityReferenceAutocompleteWidget;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\mcapi\Mcapi;
use Symfony\Component\Validator\ConstraintViolationInterface;

/**
 * Plugin implementation of the wallet selector widget.
 *
 * @FieldWidget(
 *   id = "wallet_reference_autocomplete",
 *   label = @Translation("Wallets"),
 *   description = @Translation("Autocomplete field on wallets"),
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
      'hide_one_wallet' => FALSE,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $element = parent::settingsForm($form, $form_state);
    unset($element['size']);
    $element['hide_one_wallet'] = [
      '#title' => $this->t('Hide this field if there is only one wallet available.'),
      '#type' => 'checkbox',
      '#default_value' => $this->getSetting('hide_one_wallet'),
    ];
    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = parent::settingsSummary();
    // Size.
    unset($summary[1]);
    $message = $this->getSetting('hide_one_wallet') ?
      $this->t('Hide widget for one wallet') :
      $this->t('Show widget for one wallet');
    $summary['hide_one'] = ['#markup' => $message];
    return $summary;
  }

  /**
   * {@inheritdoc}
   *
   * @see mcapi_field_widget_wallet_reference_autocomplete_form_alter
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    if ($form_state->get('restrictWallets')) {
      $restriction = $this->fieldDefinition->getName() == PAYER_FIELDNAME ? 'payout' : 'payin';
      // Used in wallet constraint validator.
      $items->restricted = TRUE;
    }
    else {
      $this->fieldDefinition->setSetting('handler_settings', []);
      $restriction = '';
      $items->restricted = FALSE;
    }

    $referenced_entities = $items->referencedEntities();
    $default_value_wallet = isset($referenced_entities[$delta]) ? $referenced_entities[$delta] : NULL;
    if ($default_value_wallet && $default_value_wallet->isIntertrading()) {
      $wids = [$default_value_wallet->id()];
    }
    else {
      // Get all payer or payee wallets regardless of direction.
      $wids = \Drupal::service('plugin.manager.entity_reference_selection')
        ->getSelectionHandler($this->fieldDefinition)
        ->queryEntities();
    }
    $count = count($wids);

    if (!$count) {
      drupal_set_message($this->t('No wallets to show for @role', ['@role' => $this->fieldDefinition->getLabel()]), 'error');
      $form['#disabled'] = TRUE;
      return [];
    }
    $max = \Drupal::config('mcapi.settings')->get('wallet_widget_max_select');
    // Present different widgets according to the number of wallets to choose
    // from, and settings.
    if ($count == 1) {
      $wid = reset($wids);
      $element['#value'] = $wid;
      if ($this->getSetting('hide_one_wallet')) {
        $element['#type'] = 'value';
      }
      else {
        $element['#type'] = 'item';
        $element['#markup'] = Wallet::load($wid)->label();
      }
    }
    elseif ($count > $max) {
      $element += [
        '#type' => 'wallet_entity_auto', //this is just a wrapper around element entity_autocomplete
        '#selection_settings' => ['direction' => $restriction],
        '#default_value' => $default_value_wallet,
        '#placeholder' => $this->getSetting('placeholder'),
      ];
    }
    else {
      $element += [
        '#type' => (\Drupal::config('mcapi.settings')->get('wallet_widget_max_radios')) ? 'select' : 'radios',
        '#options' => Mcapi::entityLabelList('mcapi_wallet', $wids),
        '#default_value' => $default_value_wallet ? $default_value_wallet->id() : '',
      ];
    }
    return ['target_id' => $element];
  }

  /**
   * {@inheritdoc}
   *
   * @note this is used on the mass transaction form to select multiple wallets
   */
  protected function formMultipleElements(FieldItemListInterface $items, array &$form, FormStateInterface $form_state) {
    $element = array(
      '#title' => $this->fieldDefinition->getLabel(),
      '#description' => $this->fieldDefinition->getDescription(),
    );
    $element = $this->formSingleElement($items, 0, $element, $form, $form_state);
    $element['target_id']['#multiple'] = TRUE;
    return $element;
  }

  /**
   * try without this, just using the parent for normal and mass transactions
   */
  public function massageFormValues(array $values, array $form, FormStateInterface $form_state) {
    //This helps the massPay form to validate when this widget manifests an array
    if (is_array(reset($values)) && key($values) == '0') {
      $values = ['target_id' => reset($values[0])];
    }
    return $values;
  }

  /**
   * {@inheritdoc}
   */
  public function errorElement(array $element, ConstraintViolationInterface $error, array $form, FormStateInterface $form_state) {
    return isset($element['target_id']) ? $element['target_id'] : FALSE;
  }


  /**
   * {@inheritdoc}
   *
   * @note this hack for the mass transaction form is probably a bad idea.
   * Instead of hacking the widget we should hack the transaction field instance
   * cardinality.
   */
  function forceMultipleValues() {
    $this->pluginDefinition['multiple_values'] = TRUE;
  }

  function inverse() {
    die('called WalletReferenceAutocompleteWidget::inverse');
  }

}
