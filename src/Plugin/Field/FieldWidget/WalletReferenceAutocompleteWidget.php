<?php
/**
 * @file
 * Contains \Drupal\mcapi\Plugin\Field\FieldWidget\WalletReferenceAutocompleteWidget.
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
  public static function defaultSettings() {
    return parent::defaultSettings() + [
      'hide_one_wallet' => FALSE
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $element = parent::settingsForm($form, $form_state);
    unset($element['size']);
    $element['hide_one_wallet'] = [
      '#title' => $this->t('Hide the wallet field if there is only one.'),
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
    unset($summary[1]);//size
    $message = $this->getSetting('hide_one_wallet') ?
      $this->t('Hide widget for one wallet') :
      $this->t('Show widget for one wallet');
    $summary['hide_one'] = ['#markup' => $message];
    return $summary;
  }

  /**
   * {@inheritdoc}
   * @see function mcapi_field_widget_wallet_reference_autocomplete_form_alter
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {

    if ($form_state->get('restrictWallets')) {
      $restriction = $this->fieldDefinition->getName() == 'payer' ? 'payout' : 'payin';
      $items->restricted = TRUE;//used in wallet constraint validator
    }
    else {
      $restriction = '';
      $items->restricted = FALSE;
    }

    $referenced_entities = $items->referencedEntities();
    $default_value_wallet = isset($referenced_entities[$delta]) ? $referenced_entities[$delta] : NULL;
    if ($default_value_wallet && $default_value_wallet->isIntertrading()) {
      $wids = [$default_value_wallet->id()];
    }
    else {
      //get all payer or payee wallets regardless of direction
      $wids = Mcapi::getWalletSelection('', $restriction);
    }
    $count = count($wids);

    if (!$count) {
      throw new \Exception('No wallets to show for '.$this->fieldDefinition->getName());
    }
    $max = \Drupal::config('mcapi.settings')->get('wallet_widget_max_select');
    //present different widgets according to the number of wallets to choose from, and settings
    if ($count == 1) {
      $wid = reset($wids);
      $element['#value'] = $wid;
      if ($this->getSetting('hide_one_wallet')) {
        $element['#type'] = 'value';
      }
      else {
        $element['#type'] = 'item';
        $element['#markup'] = \Drupal\mcapi\Entity\Wallet::load($wid)->label();
      }
    }
    elseif ($count > $max) {
      $element += [
        '#type' => 'wallet_entity_auto',
        //assuming that the field item name IS the role
        '#selection_handler' => 'default:mcapi_wallet',//this should be implicit because of the $type
        '#selection_settings' => ['restrict' => $restriction],
        '#default_value' => $default_value_wallet,
        '#placeholder' => $this->getSetting('placeholder'),
        '#size' => 60,//appearance should be managed with css
        '#maxlength' => 64,
        '#validate_reference' => FALSE,
      ];
    }
    else {
      $element += [
        '#type' => (\Drupal::config('mcapi.settings')->get('wallet_widget_max_radios')) ? 'select' : 'radios',
        '#options' => Mcapi::entityLabelList('mcapi_wallet', $wids),
        '#default_value' => $default_value_wallet ? $default_value_wallet->id() : ''
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
