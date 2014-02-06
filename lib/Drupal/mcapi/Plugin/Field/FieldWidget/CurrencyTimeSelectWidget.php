<?php

/**
 * @file
 * Contains Drupal\mcapi\Plugin\Field\FieldWidget\CurrencyTimeSelectWidgetcd .
 */

namespace Drupal\mcapi\Plugin\Field\FieldWidget;

use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Field\FieldItemListInterface;
use Symfony\Component\Validator\ConstraintViolationInterface;

/**
 * Plugin implementation of the 'text_textfield' widget.
 *
 * @FieldWidget(
 *   id = "currency_time_select",
 *   label = @Translation("Select Field"),
 *   field_types = {
 *     "currency_type_time"
 *   },
 *   settings = {
 *     "interval" = 15
 *   }
 * )
 */
class CurrencyTimeSelectWidget extends WidgetBase {

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, array &$form_state) {

  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, array &$form_state) {
    $element = parent::settingsForm($form, $form_state);

    $options = array();
    foreach (array(1, 2, 3, 4, 5, 6, 10, 12, 15, 20, 30) as $minutes) {
      $options[$minutes] = $this->t('!minutes minutes', array('!minutes' => $minutes));
    }

    $element['interval'] = array(
      '#type' => 'select',
      '#title' => $this->t('Time interval'),
      '#default_value' => $this->getSetting('interval'),
      '#options' => $options,
      '#required' => TRUE,
      '#description' => $this->t('Number of minutes per time interval. Time interval must be divisible by 60 minutes.')
    );

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = parent::settingsSummary();

    $summary[] = t('Interval: !interval minutes', array('!interval' => $this->getSetting('interval')));

    return $summary;
  }

}
