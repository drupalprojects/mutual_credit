<?php

namespace Drupal\mcapi\Plugin\Field\FieldFormatter;

use Drupal\mcapi\Entity\Currency;
use Drupal\mcapi\Entity\CurrencyInterface;
use Drupal\mcapi\Element\WorthsView;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'worth' formatter.
 *
 * @FieldFormatter(
 *   id = "worth",
 *   label = @Translation("Currency values"),
 *   field_types = {
 *     "worth"
 *   }
 * )
 */
class WorthFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $form = parent::settingsForm($form, $form_state);
    $form['format'] = [
      '#title' => $this->t("Format"),
      '#type' => 'radios',
      '#options' => $this->getOptions(),
      '#required' => TRUE,
      '#default_value' => $this->getSetting('format'),
    ];
    $form['context'] = [
      '#title' => $this->t("Context"),
      '#type' => 'radios',
      '#options' => WorthsView::options(),
      '#default_value' => $this->getSetting('context'),
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    $settings = parent::defaultSettings();
    $settings['format'] = CurrencyInterface::DISPLAY_NORMAL;
    $settings['context'] = WorthsView::MODE_TRANSACTION;
    return $settings;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    return [$this->getOptions()[$this->getSetting('format')]];
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    return [
      0 => [
        '#type' => 'worths_view',
        '#worths' => $items->getValue(),
        '#format' => $this->getSetting('format'),
        '#context' => $this->getSetting('context')
      ],
    ];

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  private function getOptions() {
    return Currency::formats();
  }

}
