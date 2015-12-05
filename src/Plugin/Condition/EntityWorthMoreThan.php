<?php

/**
 * @file
 * Contains \Drupal\mcapi\Plugin\Condition\EntityWorthMoreThan.
 *
 * @todo see https://www.drupal.org/node/2284687
 */

namespace Drupal\mcapi\Plugin\Condition;

use Drupal\Core\Condition\ConditionPluginBase;
use Drupal\Core\Form\FormStateInterface;

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

class EntityWorthMoreThan extends ConditionPluginBase {

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
  public function evaluate() {
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

}
