<?php

/**
 * @file
 *  Contains Drupal\mcapi_limits\Plugin\Limits\Calculated
 *
 */

namespace Drupal\mcapi_limits\Plugin\Limits;

use \Drupal\mcapi\Entity\WalletInterface;

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
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, array &$form_state) {
    $subform = parent::buildConfigurationForm($form, $form_state);
    $subform['max_formula'] = array(
      '#prefix' => t('N.B Result values are measured in native units, which might be cents, or seconds'),
      '#title' => t('Formula to calculate minimum limit'),
      '#description' => t('Make a formula from the following variables: @vars', array('@vars' => $this->help())),
    	'#type' => 'textfield',
      '#default_value' => $this->configuration['max_formula'],
      '#element_validate' => array(array($this, 'validate_formula')),
    );
    $subform['min_formula'] = array(
      '#title' => t('Formula to calculate minimum limit'),
      '#description' => t('Make a formula to output a NEGATIVE result from the following variables: @vars', array('@vars' => $this->help())),
    	'#type' => 'textfield',
      '#default_value' => $this->configuration['min_formula'],
      '#element_validate' => array(array($this, 'validate_formula'))
    );
    return $subform;
  }

  /**
   * @see \Drupal\mcapi_limits\McapiLimitsBase::getLimits($wallet)
   */
  public function getLimits(WalletInterface $wallet) {
    $stats = $wallet->getStats($this->currency->id());
    return array(
      'max' => $this->parse($this->configuration['max_formula'], $stats),
      'min' => $this->parse($this->configuration['min_formula'], $stats)
    );
  }

  /**
   *
   * @return string
   */
  private static function help() {
    $help[] = t('@in: the total ever income of the user');
    $help[] = t('@out: the total ever expenditure of the user.');
    $help[] = t('@num: the number of trades.');
    $help[] = t('@ptn: the number of trading partners.');
    $help[] = t('@beware division by zero!');
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
    if (empty($values)) return '';
    $replacements = array(
      $values['gross_in'] ? : 0,
      $values['gross_out'] ? : 0,
      $values['trades'] ? : 0,
      $values['partners'] ? : 0
    );
    $pattern = str_replace($tokens, $replacements, $string);
    return eval('return '. $pattern.';');
  }


  /**
   * (non-PHPdoc)
   * @see \Drupal\mcapi_limits\Plugin\Limits\McapiLimitsBase::defaultConfiguration()
   */
  public function defaultConfiguration() {
    $defaults = parent::defaultConfiguration();
    return $defaults+ array(
      'min_formula' => '-@in/2-100',
      'max_formula' => '@in/2+100',
    );
  }

}
