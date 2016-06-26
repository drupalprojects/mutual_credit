<?php

namespace Drupal\mcapi\Element;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element\Checkboxes;
use Drupal\mcapi\Mcapi;

/**
 * Provides a form element for choosing several relatives of the transaction.
 *
 * @see \Drupal\mcapi\TransactionRelativeManager
 *
 * @FormElement("transaction_relatives")
 */
class Relatives extends Checkboxes {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    $info = parent::getInfo();
    $info['#description'] = t('Check whichever relatives apply');
    return $info;
  }

  /**
   * {@inheritdoc}
   */
  public static function processCheckboxes(&$element, FormStateInterface $form_state, &$complete_form) {
    $element['#options'] = Mcapi::transactionRelatives()->options();
    return parent::processCheckboxes($element, $form_state, $complete_form);
  }

}
