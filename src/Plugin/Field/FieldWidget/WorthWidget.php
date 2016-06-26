<?php

namespace Drupal\mcapi\Plugin\Field\FieldWidget;

use Drupal\mcapi\Mcapi;
use Drupal\mcapi\Entity\Currency;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\Validator\ConstraintViolationInterface;

/**
 * Plugin implementation of the 'worth' widget.
 *
 * @FieldWidget(
 *   id = "worth",
 *   label = @Translation("Worth"),
 *   multiple_values = 1,
 *   field_types = {
 *     "worth"
 *   },
 *   multiple_values = TRUE
 * )
 */
class WorthWidget extends WidgetBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'exclude' => FALSE,
      'currencies' => [],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $element['exclude'] = [
      '#title' => $this->t('Exclude'),
      '#type' => 'checkbox',
      '#default_value' => $this->getSetting('exclude'),
    ];
    $element['currencies'] = [
      '#title' => $this->t('Limit the currences'),
      '#type' => 'mcapi_currency_select',
      '#multiple' => TRUE,
      '#default_value' => $this->getSetting('currencies'),
    ];
    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $message = '';
    $currencies = Currency::loadMultiple($this->getSetting('currencies'));
    $names = Mcapi::entityLabelList('mcapi_currency', array_values($currencies));
    if ($this->getSetting('exclude')) {
      $message .= $this->t('Not: @names', ['@names' => implode(', ', $names)]);
    }
    return ['#markup' => $message];
  }

  /**
   * Returns the form for a single field widget.
   *
   * @see \Drupal\Core\Field\WidgetInterface
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {

    $curr_ids = array_filter($this->getSetting('currencies'));

    $this->getSetting('exclude');
    if ($this->getSetting('exclude')) {
      $curr_ids = array_diff(array_keys(Currency::loadMultiple()), $curr_ids);
    }

    // Because this is a multiple widget, ignore delta value and put all items.
    $element += array(
      '#title' => $this->fieldDefinition->label(),
      '#title_display' => 'attribute',
      '#type' => 'worths_form',
      '#default_value' => $items->getValue() ?: NULL,
      '#allowed_curr_ids' => $curr_ids,
    );

    unset($element['#description']);
    return $element;
  }

  /**
   * {@inheritdoc}
   *
   * @todo test this errorElement
   */
  public function errorElement(array $element, ConstraintViolationInterface $violation, array $form, FormStateInterface $form_state) {
    return $element[$violation->getpropertyPath()];
  }

}
