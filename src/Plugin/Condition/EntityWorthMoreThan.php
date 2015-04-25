<?php

/**
 * @file
 * Contains \Drupal\mcapi\Plugin\Condition\EntityWorthMoreThan.
 */

namespace Drupal\mcapi\Plugin\Condition;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Condition\ConditionPluginBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a 'Worth field is more than' condition.
 * 
 * @todo is the worth field multiple?
 * @todo make a worth @contextDefinition
 *
 * @Condition(
 *   id = "mcapi_transaction_type",
 *   label = @Translation("Transaction is of type"),
 *   category = @Translation("Community Accounting"),
 *   context = {
 *     "entity" = @ContextDefinition("entity",
 *       label = @Translation("Transaction"),
 *       description = @Translation("Specifies the entity with a worth field.")
 *     ),
 *     "fieldname" = @ContextDefinition("string",
 *       label = @Translation("Machine name of the worth field")
 *     ),
 *     "worth" = @ContextDefinition("entity:mcapi_type",
 *       label = @Translation("Types"),
 *       description = @Translation("All the possible transaction types"),
 *     ),
 *   }
 * )
 *
 * @todo: Add access callback information from Drupal 7.
 * @todo: Add group information from Drupal 7.
 */
  
class EntityWorthMoreThan extends ConditionPluginBase implements ContainerFactoryPluginInterface {

  //todo Check this later. This injection seems highly unusually formatted
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition
    );
  }
  
  /**
   * {@inheritdoc}
   */
  public function summary() {
    return $this->t('Transaction is worth at least');
  }

  /**
   * {@inheritdoc}
   */
  public function evaluate() {
    $entity = $this->getContextValue('entity');
    $fieldname = $this->getContextValue('fieldname');
    $worths = $this->getContextValue('worth');
    foreach ($worths as $worth) {
      if (is_null($worth->value)) continue;
      if ($entity->{$fieldname}->getValue($worth->curr_id)  > $worth->value) {
        continue;
      }
      return FALSE;
    }
    return TRUE;
  }

}
