<?php

/**
 * @file
 * Contains \Drupal\mcapi\Plugin\Field\Widget\SelectWalletWidget.
 * @deprecated
 */

namespace Drupal\mcapi\Plugin\Field\FieldWidget;

use Drupal\Core\Field\Plugin\Field\FieldWidget\EntityReferenceAutocompleteWidget;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Field\FieldItemListInterface;

/**
 * Plugin implementation of the 'select wallet' widget.
 *
 * @FieldWidget(
 *   id = "select_wallet",
 *   label = @Translation("Wallets"),
 *   description = @Translation("Autocomplete field on wallets"),
 *   field_types = {
 *     "wallet"
 *   }
 * )
 */
class SelectWalletWidget extends EntityReferenceAutocompleteWidget {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'role' => '',
    ] + parent::defaultSettings();
  }
  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $form = parent::settingsForm($form, $form_state);
    //'role' is in the sense of payer or payee, in the transaction.
    $form['role'] = [
      '#title' => $this->t('Role'),
      '#type' => 'radios',
      '#options' => $this->options(),
      '#default_value' => $this->getSetting('role'),
      '#required' => TRUE
    ];
    return $form;
    //ensure the placeholder is used
  }

  private function options() {
    return [
      'payer' => $this->t('Payer'),
      'payee' => $this->t('Payee')
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    return array_merge(
      [$this->options()[$this->getSetting('role')]],
      parent::settingsSummary()
    );
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $referenced_entities = $items->referencedEntities();
    $op = $this->getSetting('role') == 'payer' ? 'payout' : 'payin';
    //@todo inject entityMansger
    $wids = \Drupal::entityManager()
      ->getStorage('mcapi_wallet')
      ->walletsUserCanActOn($op, \Drupal::currentUser());

    if (count($wids) > \Drupal::config('mcapi.wallets')->get('threshhold')) {
      $element += [
        '#type' => 'select_wallet',
        //assuming that the field item name IS the role
        '#autocomplete_route_parameters' => ['role' => $this->getSetting('role')],
        '#default_value' => isset($referenced_entities[$delta]) ? $referenced_entities[$delta] : NULL,
        '#placeholder' => $this->getSetting('placeholder'),
        '#size' => $this->getSetting('size'),
        '#maxlength' => 64,
        //@todo check that entity reference field items are handling validation themselves via
        //the 'ValidReference' constraint.
        '#validate_reference' => FALSE,
      ];
    }
    else {
      $element += [
         //@todo TEMP
        '#type' => 'select',//\Drupal::config('mcapi.wallets')->get('widget'),
        '#options' => mcapi_entity_label_list('mcapi_wallet', $wids),
        '#default_value' => isset($referenced_entities[$delta]) ? $referenced_entities[$delta]->id() : ''
      ];
    }
    return ['target_id' => $element];
  }


}
