<?php

/**
 * @file
 * Contains \Drupal\Core\Field\Plugin\Field\FieldFormatter\WorthFormatter.
 * @todo this should be three different formatters
 */

namespace Drupal\mcapi\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\mcapi\Entity\Currency;

/**
 * Plugin implementation of the 'worth' formatter.
 * @todo inject config
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
    //at the moment the curr_id setting can only be determined in Drupal\mcapi\Plugin\views\field\Worth
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    $settings = parent::defaultSettings();
    $settings['format'] = Currency::DISPLAY_NORMAL;
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
    $elements = [
      0 => [
        '#type' => 'worths_view',
        '#format' => $this->getSetting('format'),
        '#worths' => $items->getValue()
      ]
    ];
    return $elements;
  }

  private function getOptions() {
    return Currency::formats();
  }
}