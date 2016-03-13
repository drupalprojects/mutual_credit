<?php

/**
 * @file
 * Contains \Drupal\mcapi\Plugin\Condition\EntityWorthMoreThan.
 *
 * @todo see https://www.drupal.org/node/2284687
 */

namespace Drupal\mcapi\Plugin\Condition;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Provides a 'Worth field is more than' condition.
 *
 * @Condition(
 *   id = "mcapi_worth_more_than",
 *   label = @Translation("Entity is worth more than"),
 *   category = @Translation("Community Accounting"),
 *   context = {
 *     "entity" = @ContextDefinition("entity",
 *       label = @Translation("Entity"),
 *       description = @Translation("Specifies the entity with a worth field.")
 *     ),
 *     "fieldname" = @ContextDefinition("string",
 *       label = @Translation("Machine name of the worth field")
 *     ),
 *     "worth" = @ContextDefinition("entity:worth",
 *       label = @Translation("Worth"),
 *       description = @Translation("the value of the worth field"),
 *     ),
 *   }
 * )
 *
 */

class EntityWorthMoreThan extends \Drupal\rules\Core\RulesConditionBase {

  private $worth;

  /**
   * {@inheritdoc}
   */
  public function summary() {
    return $this->t(
      'Entity is worth at least !value',
      ['%value' => $this->worth->format()]
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'worth' => [],
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form['worth'] = [
      '#description' => '@todo work out how this works with multiple values or whether to restrict it to one value',
      '#type' => 'worth',
      '#default_value' => $this->configuration['worth'],
    ];
    return parent::buildConfigurationForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function doEvaluate($args) {
    $entity = $this->getContextValue('entity');
    $fieldname = $this->getContextValue('fieldname');
    $worths = $this->getContextValue('worth');
    foreach ($worths as $worth) {
      if (is_null($worth->value)) {
        continue;
      }
      if ($entity->{$fieldname}->getValue($worth->curr_id)  > $worth->value) {
        continue;
      }
      return FALSE;
    }
    return TRUE;
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

  public function refineContextDefinitions(array $selected_data){}


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
