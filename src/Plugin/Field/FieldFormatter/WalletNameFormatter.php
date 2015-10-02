<?php

/**
 * @file
 * Contains \Drupal\Core\Field\Plugin\Field\FieldFormatter\WalletNameFormatter.
 *
 * @deprecated
 */

namespace Drupal\mcapi\Plugin\Field\FieldFormatter;

use Drupal\Component\Utility\String;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;

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
class WalletNameFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $wallet = reset($items->referencedEntities());
    $replacements = [
      '{{ wallet_id }}' => $wallet->id(),
      '{{ wallet_name }}' => $wallet->name->value,//@todo checkplain
      '{{ owner_label }}' => $wallet->getowner()->label(),
      '{{ owner_type }}' => $wallet->getowner()->getEntityType()->label
    ];
//try this with #markup?
    return [0 => strtr($this->options['template'], $replacements)];

  }


  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $form = parent::settingsForm($form, $form_state);
    $form['template'] = [
      '#title' => $this->t('Wallet name template'),
      '#description' => $this->t('Arrange any of the following tokens, considering that wallet names are optional.') .' '.
        '{{ wallet_id }}, {{ wallet_name }}, {{ owner_label }}, {{ owner_type }}',
      '#type' => 'textfield',
      '#default_value' => $this->getSetting('template')
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    return ['#markup' => $this->getSetting('template')];
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    $settings = parent::defaultSettings();
    $settings['template'] = '#{{ wallet_id }}: {{ owner_type }} {{ owner_label }} {{ wallet_name }}';
    return $settings;
  }

}
