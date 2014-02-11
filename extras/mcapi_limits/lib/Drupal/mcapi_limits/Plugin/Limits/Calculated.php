<?php

/**
 * @file
 *  Contains Drupal\mcapi_limits\Plugin\Limits\Calculated
 *
 */

namespace Drupal\mcapi_limits\Plugin\Limits;


use \Drupal\mcapi_limits\McapiLimitsBase;
use \Drupal\mcapi_limits\McapiLimitsInterface;
use \Drupal\Core\Entity\EntityInterface;

/**
 * Calculate the balance limits
 *
 * @Limits(
 *   id = "calculated",
 *   label = @Translation("Calculated"),
 *   description = @Translation("Calculate from user summary stats")
 * )
 */
class Calculated extends McapiLimitsBase implements McapiLimitsInterface {

  /**
   * @see \Drupal\mcapi_limits\McapiLimitsBase::settingsForm()
   */
  public function settingsForm() {
    $preset = (array)$this->limits_settings;
    $form['max_formula'] =  array(
      '#prefix' => t('N.B Result values are measured in native units, which might be cents, or seconds'),
      '#title' => t('Formula to calculate minimum limit'),
      '#description' => t('Make a formula from the following variables: @vars', array('@vars' => $this->help())),
    	'#type' => 'textfield',
      '#default_value' => array_key_exists('max_formula', $preset) ? $preset['max_formula'] : '',
      '#element_validate' => array(array($this, 'validate_formula'))
    );
    $form['min_formula'] =  array(
      '#title' => t('Formula to calculate minimum limit'),
      '#description' => t('Make a formula from the following variables: @vars', array('@vars' => $this->help())),
    	'#type' => 'textfield',
      '#default_value' => array_key_exists('min_formula', $preset) ? $preset['min_formula'] : '',
      '#element_validate' => array(array($this, 'validate_formula'))
    );
    $form += parent::settingsForm();
    return $form;
  }
  /**
   * @see \Drupal\mcapi_limits\McapiLimitsInterface::checkPayer()
   */
  public function checkPayer(EntityInterface $wallet, $diff) {
    $limits = $this->getLimits($account);
    return TRUE;
  }

  /**
   * @see \Drupal\mcapi_limits\McapiLimitsInterface::checkPayee()
   */
  public function checkPayee(EntityInterface $wallet, $diff) {
    $limits = $this->getLimits($account);
    return TRUE;
  }

  /**
   * @see \Drupal\mcapi_limits\McapiLimitsBase::getLimits($wallet)
   */
  public function getLimits(EntityInterface $wallet) {
    $stats = $wallet->getStats($this->currency->id());
    return array(
      'max' => $this->parse($this->limits_settings['max_formula'], $stats),
      'min' => $this->parse($this->limits_settings['min_formula'], $stats)
    );
  }

  /**
   *
   * @return string
   */
  private function help() {
    $help[] = t('@in: the total ever income of the user');
    $help[] = t('@out: the total ever expenditure of the user.');
    $help[] = t('@num: the number of trades.');
    $help[] = t('@ptn: the number of trading partners.');
    return implode('; ', $help);
  }

  /**
   *
   * @param unknown $element
   * @param unknown $form_state
   */
  public function validate_formula($element, &$form_state) {
    if (!strlen($element['#value'])) return;
    $test_values = array('gross_in' => 110, 'gross_out' => 90, 'trades' => 10, 'partners' => 5);
    $value = $this->parse($element['#value'], $test_values);

    if (!is_numeric($value)) {
      //TODO display this error properly
      form_error($element, t('Formula does not evaluate to a number: @result', array('@result' => $result)));
    }
  }

  /**
   *
   * @param unknown $string
   * @param unknown $values
   */
  private function parse($string, $values) {
    $tokens = array('@in', '@out', '@num', '@ptn');
    $replacements = array(
      $values['gross_in'],
      $values['gross_out'],
      $values['trades'],
      $values['partners']
    );
    $pattern = str_replace($tokens, $replacements, $string);
    return eval('return '. $pattern.';');
  }

}
