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
    $settings['format'] = Currency::FORMAT_NORMAL;
    $settings['curr_ids'] = [];
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
    $output = [];
    $curr_ids = $this->getSetting('curr_ids');
    $delimiter = \Drupal::config('mcapi.settings')->get('worths_delimiter');
    foreach ($items as $item) {
      $curr_id = $item->currency->id();
      if ($curr_ids && !in_array($curr_id, $curr_ids)) {
         continue;
      }
      if ($item->value) {
        $formatted = $item->currency->format($item->value, $this->getSetting('format'));
        //we don't have the luxury of a theme callback here so just going to shoehorn in the div wrapper
        //needed to do different css on worths per currency
        $tag = $delimiter ? 'span' : 'div';
        $output[] = "<$tag class = \"worth-\"{$curr_id}\">" . $formatted . "</$tag>";
      }
      else {
        //apply any special formatting for zero value transactions
        if ($item->currency->zero) {
          if ($this->getSetting('format') == Currency::FORMAT_NORMAL) {
            $output[] = \Drupal::config('mcapi.settings')->get('zero_snippet');
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
    //@todo this isn't #markup coz its safe already
    $elements[0]['#markup'] = implode(
      $delimiter,
      $output
    );
    return $elements;
  }

  private function getOptions() {
    return Currency::formats();
  }
}