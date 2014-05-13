<?php


/**
 * @file
 * Contains \Drupal\mcapi\Plugin\Field\FieldWidget\WorthfieldWidget.
 */

namespace Drupal\mcapi\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Symfony\Component\Validator\ConstraintViolationInterface;
use Drupal\Component\Utility\String;

/**
 * Plugin implementation of the 'worth' widget.
 * No settings, I think
 *
 * @FieldWidget(
 *   id = "worth_known_cur",
 *   label = @Translation("Worth (currency is known)"),
 *   multiple_values = 1,
 *   field_types = {
 *     "worth"
 *   }
 * )
 */
class WorthfieldWidget extends WidgetBase {

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, array &$form_state) {
    //might want a placeholder setting
    $element = array(
    	'1' => array(
    	  '#markup' => 'This is the settings form for the worth_known_cur widget'
      )
    );
    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = array();
/*
    $summary[] = t('Textfield size: !size', array('!size' => $this->getFieldSetting('size')));
    $placeholder = $this->getFieldSetting('placeholder');
    if (!empty($placeholder)) {
      $summary[] = t('Placeholder: @placeholder', array('@placeholder' => $placeholder));
    }
    */
    $summary[] = 'worth_known_cur settings summary';
    return $summary;
  }

  public function form(FieldItemListInterface $items, array &$form, array &$form_state, $get_delta = NULL) {
    /*
    $element = $this->formSingleElement($items, $delta, $element, $form, $form_state);
    if (isset($get_delta)) {
      $elements[$delta] = $element;
    }
    else {
      $elements = $element;
    }
    */
    $elements = $this->formMultipleElements($items, $form, $form_state);
    return $elements;
  }

  /**
   * Returns the form for a single field widget.
   *
   * Note that $delta is a currency id, not a counter, and it can be blank
   *
   * @see \Drupal\Core\Field\WidgetInterface
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, array &$form_state) {
    //we can presume the existence of ($element[#currency])
//    $default_val = $items[$delta];
    $default_val = 0;

    $currency = mcapi_currency_load($element['#currcode']);
    return array(
      '#title' => $currency->label(),
      '#title_display' => 'attribute',
      '#type' => 'worth',
    	'#default_value' => $default_val,//raw integer
      '#weight' => $currency->weight,
      //'#currency' => mcapi_currency_load($delta),
      '#theme_wrappers' => array('form_element'),
    ) + $element;
  }

  /**
   * {@inheritdoc}
   */
  public function errorElement(array $element, ConstraintViolationInterface $violation, array $form, array &$form_state) {
    if ($violation->arrayPropertyPath == array('format') && isset($element['format']['#access']) && !$element['format']['#access']) {
      // Ignore validation errors for formats if formats may not be changed,
      // i.e. when existing formats become invalid. See filter_process_format().
      return FALSE;
    }
    return $element;
  }


  /**
   * Special handling to create form elements for multiple values.
   * overrides Widgetbase::formMultipleElements() using the currcode instead of the incremental delta
   *
   * //TODO remove cardinality settings and replace with currency constrainer
   * Handles generic features for multiple fields:
   */
  protected function formMultipleElements(FieldItemListInterface $items, array &$form, array &$form_state) {
    $field_name = $this->fieldDefinition->getName();
    $cardinality = $this->fieldDefinition->getCardinality();
    $parents = $form['#parents'];

    $title = String::checkPlain($this->fieldDefinition->getLabel());
    $description = field_filter_xss(\Drupal::token()->replace($this->fieldDefinition->getDescription()));

    // Determine the currcodes of widgets to display.
    //A payment form is between two wallets, which we don't know yet.
    //Here we want to show all the currencies that the current user might be able to pay with
    //the transaction form validation will work out if the other wallet can hold such currencies.
    //by virtue of which exchanges it is in
    //GET all the currencies in all the exchanges of the current user.

    $elements = array(
      '#title' => $title,
      '#description' => $description,
      '#tree' => TRUE,
      '#theme_wrappers' => array('form_element'),
    );
    foreach (mcapi_currencies_for_user() as $currency) {
      $currcode = $currency->id();
      $element['#currcode'] = $currcode;
      //nb we are sending the #currency and the $currency->id() instead of the delta
      $elements[] = $this->formSingleElement($items, $currcode, $element, $form, $form_state);
    }
    return $elements;
  }

}

/**
 * Leftover code for building multiple form elements

  $class = \Drupal::entityManager()->getDefinition('mcapi_currency')->getClass();
  //print_r($class);
  //I'm not sure the difference between these sorting methods.
  //uasort($currencies, array('\Drupal\Component\Utility\SortArray', 'sortByWeightProperty'));
  uasort($currencies, array($class, 'sort'));

  foreach ($currencies as $currency) {

    continue;


    //get one widget for each currency
    //the widgets appear differently depending if there is one or more than one
    if (empty($element[$currency->widget])) {
      $element[$currency->id()] = array(
        '#title_display' => 'invisible',
      );
      if (count($element['#currcodes']) > 1) {
        // Set up the dependent fields when the currency select box is changed.
        $element[$currency->widget]['#states'] = array(
          'visible' => array(
            ':input[name="' . $name . '"]' => array(
              array('value' => $currency->id()),
            ),
          ),
        );
      }
    }
    else {
      // add the new additional currency.
      $element[$currency->widget]['#states']['visible'][':input[name="' . $name . '"]'][] = array('value' => $currency->id());
    }

  }
  return $element;
  */