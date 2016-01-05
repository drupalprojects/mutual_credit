<?php
/**
 * @file
 * Contains \Drupal\mcapi\Plugin\Field\Widget\WalletReferenceAutocompleteWidget.
 * @deprecated
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
 *     "wallet"
 *   }
 * )
 */
class WalletReferenceAutocompleteWidget extends EntityReferenceAutocompleteWidget {
  
  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $form = parent::settingsForm($form, $form_state);
    unset($form['size']);
    return $form;
    //ensure the placeholder is used
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
    $op = ($this->fieldDefinition->getName() == 'payer' ? 'payin' : 'payout');
    //@todo inject entityMansger
    $wids = Mcapi::whichWallets($op, \Drupal::currentUser()->id());
    $count = count($wids);
    $config = \Drupal::config('mcapi.settings');
    //present different widgets according to the number of wallets to choose from, and settings
    if ($count == 1) {
      $element += [
        '#type' => 'hidden',
        '#value' => reset($wids)
      ];
    }
    elseif ($count > $config->get('wallet_widget_max_select')) {
      $element += [
        '#type' => 'wallet_entity_auto',
        //assuming that the field item name IS the role
        '#selection_handler' => 'default:wallet',//this should be implicit because of the $type
        '#selection_settings' => ['op' => $op],
        '#default_value' => isset($referenced_entities[$delta]) ? $referenced_entities[$delta] : NULL,
        '#placeholder' => $this->getSetting('placeholder'),
        '#size' => 60,//appearance should be managed with css
        '#maxlength' => 64,
        '#validate_reference' => FALSE,
      ];
    }
    else {
      if($count > $config->get('wallet_widget_max_radios')) {
        $element['#type'] = 'select';
      }
      else {
        $element['#type'] = 'radios';
      }
      $element += [
        '#options' => Mcapi::entityLabelList('mcapi_wallet', $wids),
        '#default_value' => isset($referenced_entities[$delta]) ? $referenced_entities[$delta]->id() : ''
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
