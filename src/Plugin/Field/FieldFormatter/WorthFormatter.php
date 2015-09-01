<?php

/**
 * @file
 * Contains \Drupal\Core\Field\Plugin\Field\FieldFormatter\WorthFormatter.
 * @todo this should be three different formatters
 */

namespace Drupal\mcapi\Plugin\Field\FieldFormatter;

use Drupal\Component\Utility\String;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;

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
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    $settings = parent::defaultSettings();
    $settings['format'] = 'normal';
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
  public function viewElements(FieldItemListInterface $items) {
    $output = [];
    foreach ($items as $item) {
      if ($item->value) {
        $output[] = $item->currency->format($item->value, $this->getSetting('format'));
      }
      else {
        //apply any special formatting for zero value transactions
        if ($item->currency->zero) {
          if ($this->getSetting('format') == 'normal') {
            $output[] = \Drupal::config('mcapi.misc')->get('zero_snippet');
          }
          else {
            $output[] = 0;
          }
        }
        else {
          drupal_set_message("Zero value shouldn't be possible in ".$item->curr_id, 'warning');
        }
      }
    }
    //we're shovelling all the $items into 1 element because the have already
    //been rendered together, with a separator character
    $elements[0]['#markup'] = implode(
      \Drupal::config('mcapi.misc')->get('worths_delimiter'),
      $output
    );
    return $elements;
  }

  private function getOptions() {
    return [
      'normal' => $this->t('Normal'),
      'native' => $this->t('Native integer'),
      'decimalised' => $this->t('Force decimal (Rarely needed)')
    ];
  }
}