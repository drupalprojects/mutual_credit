<?php


/**
 * @file
 * Contains \Drupal\mcapi\Plugin\Field\FieldWidget\WorthWidget.
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
 *   id = "worth",
 *   label = @Translation("Worth"),
 *   multiple_values = 1,
 *   field_types = {
 *     "worth"
 *   }
 * )
 */
class WorthWidget extends WidgetBase {

  /**
   * Returns the form for a single field widget.
   *
   * @see \Drupal\Core\Field\WidgetInterface
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, array &$form_state) {
    $passed_worths = $items->getValue();
    $limit_worth_to_passed = FALSE;

    //determine the currencies to show...
    //we get all the available currencies (to the current user)
    //and if any have been passed in the items, limit selection to those
    $all_available = exchange_currencies(referenced_exchanges(NULL, TRUE));
    if ($passed_worths && $limit_worth_to_passed) {//this never happens while $limit_worth_to_passed = FALSE;
//      $all_available = array_intersect_key($passed_worths, $all_available);
//      if (count($all_available) < count($passed_worths)) {
//        $diff = mcapi_entity_label_list('mcapi_currency', array_diff_key($passed_worths, $all_available));
//        $message = t('User !name cannot handle !names', array('!names' => implode(', ', $diff)));
//        throw new mcapiTransactionException('worth', $message);
//      }
    }
    else {
      //change the all_available array to a worths value array populated by zeros
      foreach ($all_available as &$currency)$currency = 0;
    }
    return array(
      '#title' => String::checkPlain($this->fieldDefinition->getLabel()),
      '#title_display' => 'attribute',
      '#type' => 'worth',
      '#default_value' => $all_available,
      '#theme_wrappers' => array('form_element'),
    ) + $element;
  }

  function massageFormValues(array $values, array $form, array &$form_state) {
    foreach ($values as $curr_id => $value){
      $list[] = array('curr_id' => $curr_id, 'value' => $value);
    }
    return $list;
  }

  /**
   * {@inheritdoc}
   */
  public function errorElement(array $element, ConstraintViolationInterface $violation, array $form, array &$form_state) {
    echo "error element hasn't been written yet";
    if ($violation->arrayPropertyPath == array('format') && isset($element['format']['#access']) && !$element['format']['#access']) {
      // Ignore validation errors for formats if formats may not be changed,
      // i.e. when existing formats become invalid. See filter_process_format().
      return FALSE;
    }
    return $element;
  }

}
