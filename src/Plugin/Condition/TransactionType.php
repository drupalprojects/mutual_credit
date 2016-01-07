<?php

/**
 * @file
 * Contains \Drupal\mcapi\Plugin\Condition\TransactionType.
 */

namespace Drupal\mcapi\Plugin\Condition;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Provides a 'Transaction is of type' condition.
 * @todo see https://www.drupal.org/node/2284687
 *
 * @Condition(
 *   id = "mcapi_transaction_type",
 *   label = @Translation("Transaction is of type"),
 *   category = @Translation("Community Accounting"),
 *   context = {
 *     "transaction" = @ContextDefinition("entity:mcapi_transaction",
 *       label = @Translation("Transaction"),
 *     ),
 *     "types" = @ContextDefinition("entity:mcapi_type",
 *       label = @Translation("Types"),
 *       description = @Translation("Check for the allowed transaction types"),
 *       multiple = TRUE
 *     )
 *   }
 * )
 *
 */
class TransactionType extends \Drupal\rules\Core\RulesConditionBase {

  /**
   * {@inheritdoc}
   */
  public function summary() {
    //hopefully the type names are translated
    $states = array_filter($this->configuration['states']);
    return $this->t(
      'Transaction type is @types',
      ['@types' => implode(', ', $this->configuration['types'])]
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'types' => [],
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form['types'] = [
      '#type' => 'mcapi_types',
      '#default_value' => $this->configuration['types'],
      '#multiple' => TRUE
    ];
    return parent::buildConfigurationForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function doEvaluate($args) {
    $transaction = $this->getContextValue('transaction');
    $types = $this->getContextValue('types');
    return in_array($transaction->type->target_id, $types);
  }
  
  
  /**
   * {@inheritdoc}
   * @todo
   */
  public function negate($negate = TRUE) {
    
  }
  
  /**
   * Determines whether condition result will be negated.
   *
   * @return bool
   *   Whether the condition result will be negated.
   */
  public function isNegated(){}
  
  /**
   * Refines used and provided context definitions based upon context values.
   *
   * When a plugin is configured half-way or even fully, some context values are
   * already available upon which the definition of subsequent or provided
   * context can be refined.
   */
  public function refineContextDefinitions(){}
  

  /**
   * Sets the value for a provided context.
   *
   * @param string $name
   *   The name of the provided context in the plugin definition.
   * @param mixed $value
   *   The value to set the provided context to.
   *
   * @return $this
   */
  public function setProvidedValue($name, $value){}

  /**
   * Gets a defined provided context.
   *
   * @param string $name
   *   The name of the provided context in the plugin definition.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   *   If the requested provided context is not set.
   *
   * @return \Drupal\Core\Plugin\Context\ContextInterface
   *   The context object.
   */
  public function getProvidedContext($name){}

  /**
   * Gets a specific provided context definition of the plugin.
   *
   * @param string $name
   *   The name of the provided context in the plugin definition.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   *   If the requested provided context is not defined.
   *
   * @return \Drupal\Component\Plugin\Context\ContextDefinitionInterface.
   *   The definition of the provided context.
   */
  public function getProvidedContextDefinition($name){}

  /**
   * Gets the provided context definitions of the plugin.
   *
   * @return \Drupal\Component\Plugin\Context\ContextDefinitionInterface[]
   *   The array of provided context definitions, keyed by context name.
   */
  public function getProvidedContextDefinitions(){}
  
  
  /**
   * Check configuration access.
   *
   * @param AccountInterface $account
   *   (optional) The user for which to check access, or NULL to check access
   *   for the current user. Defaults to NULL.
   * @param bool $return_as_object
   *   (optional) Defaults to FALSE.
   *
   * @return bool|\Drupal\Core\Access\AccessResultInterface
   *   The access result. Returns a boolean if $return_as_object is FALSE (this
   *   is the default) and otherwise an AccessResultInterface object.
   *   When a boolean is returned, the result of AccessInterface::isAllowed() is
   *   returned, i.e. TRUE means access is explicitly allowed, FALSE means
   *   access is either explicitly forbidden or "no opinion".
   */
  public function checkConfigurationAccess(AccountInterface $account = NULL, $return_as_object = FALSE){}
}
