<?php
/**
 * @file
 * Contains \Drupal\mcapi\Plugin\Field\Widget\WalletReferenceAutocompleteWidget.
 * @todo inject entityTypeManager and config('mcapi.settings')
 */

namespace Drupal\mcapi\Plugin\Field\FieldWidget;

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
 */
class WalletReferenceAutocompleteWidget extends EntityReferenceAutocompleteWidget {

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $element = parent::settingsForm($form, $form_state);
    unset($element['size']);
    return $element;
  }


  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = parent::settingsSummary();
    unset($summary[1]);//size
    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $referenced_entities = $items->referencedEntities();
    $default_value = isset($referenced_entities[$delta]) ? $referenced_entities[$delta] : NULL;
    if ($form_state->getFormObject()->restrict) {
      $items->restriction = $this->fieldDefinition->getName() == 'payer' ? 'payout' : 'payin';
    }
    //$items restriction is needed for validation
    else $items->restriction = '';
    if ($default_value && $default_value->isIntertrading()) {
      $wids = [$default_value->id()];
    }
    else {
      $wids = Mcapi::getWalletSelection('', $items->restriction);
    }

    $count = count($wids);

    if (!$count) {
      throw new \Exception('No wallets to show for '.$this->fieldDefinition->getName());
    }
    $config = \Drupal::config('mcapi.settings');
    //present different widgets according to the number of wallets to choose from, and settings
    if ($count == 1) {
      $element += [
        '#type' => 'value',
        '#value' => reset($wids)
      ];
    }
    elseif ($count > $config->get('wallet_widget_max_select')) {
      $element += [
        '#type' => 'wallet_entity_auto',
        //assuming that the field item name IS the role
        '#selection_handler' => 'default:mcapi_wallet',//this should be implicit because of the $type
        '#selection_settings' => ['restrict' => $items->restriction],
        '#default_value' => $default_value,
        '#placeholder' => $this->getSetting('placeholder'),
        '#size' => 60,//appearance should be managed with css
        '#maxlength' => 64,
        '#validate_reference' => FALSE,
      ];
    }
    else {
      $element += [
        '#type' => ($config->get('wallet_widget_max_radios')) ? 'select' : 'radios',
        '#options' => Mcapi::entityLabelList('mcapi_wallet', $wids),
        '#default_value' => $default_value ? $default_value->id() : ''
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

}
