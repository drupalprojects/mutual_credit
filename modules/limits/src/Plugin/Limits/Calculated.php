<?php

namespace Drupal\mcapi_limits\Plugin\Limits;

use Drupal\mcapi\Entity\WalletInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\mcapi_limits\Plugin\McapiLimitsBase;

/**
 * Calculate the balance limits.
 *
 * @Limits(
 *   id = "calculated",
 *   label = @Translation("Calculated"),
 *   description = @Translation("Calculate from user summary stats")
 * )
 */
class Calculated extends McapiLimitsBase {

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $subform = parent::buildConfigurationForm($form, $form_state);
    $class = get_class($this);
    $subform['max_formula'] = [
      '#prefix' => $this->t('N.B Result values are measured in native units, which might be cents, or seconds'),
      '#title' => $this->t('Formula to calculate maximum limit'),
      '#description' => $this->t('Make a formula from the following variables: @vars', ['@vars' => $this->help()]),
      '#type' => 'textfield',
      '#default_value' => $this->configuration['max_formula'],
      '#element_validate' => [
        [$class, 'validateFormula'],
      ],
    ];
    $subform['min_formula'] = [
      '#title' => $this->t('Formula to calculate minimum limit'),
      '#description' => $this->t('Make a formula to output a NEGATIVE result from the following variables: @vars', ['@vars' => $this->help()]),
      '#type' => 'textfield',
      '#default_value' => $this->configuration['min_formula'],
      '#element_validate' => [
        [$class, 'validateFormula'],
      ],
    ];
    return $subform;
  }

  /**
   * {@inheritdoc}
   */
  public function getLimits(WalletInterface $wallet) {
    $stats = $wallet->getStats($this->currency->id());
    return [
      'max' => $this->parse($this->configuration['max_formula'], $stats),
      'min' => $this->parse($this->configuration['min_formula'], $stats),
    ];
  }

  /**
   * {@inheritdoc}
   */
  private static function help() {
    $help[] = t('@in: the total ever income of the user');
    $help[] = t('@out: the total ever expenditure of the user.');
    $help[] = t('@num: the number of trades.');
    $help[] = t('@ptn: the number of trading partners.');
    $help[] = t('Beware division by zero!');
    return implode('; ', $help);
  }

  /**
   * Element validation callback.
   */
  public static function validateFormula(&$element, $form_state) {
    if (!strlen($element['#value'])) {
      return;
    }
    $test_values = [
      'gross_in' => 110,
      'gross_out' => 90,
      'trades' => 10,
      'partners' => 5,
    ];
    $result = Self::parse($element['#value'], $test_values);

    if (!is_numeric($value)) {
      // @todo display this error properly
      $form_state->setError(
        $element,
        $this->t('Formula does not evaluate to a number: @result', ['@result' => $result])
      );
    }
  }

  /**
   * Helper.
   */
  private static function parse($string, $values) {
    $tokens = ['@in', '@out', '@num', '@ptn'];
    if (empty($values)) {
      return '';
    }
    $replacements = [
      $values['gross_in'] ?: 0,
      $values['gross_out'] ?: 0,
      $values['trades'] ?: 0,
      $values['partners'] ?: 0,
    ];
    $pattern = str_replace($tokens, $replacements, $string);
    return eval('return ' . $pattern . ';');
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    $defaults = parent::defaultConfiguration();
    return $defaults + [
      'min_formula' => '-@in/2-100',
      'max_formula' => '@in/2+100',
    ];
  }

}
