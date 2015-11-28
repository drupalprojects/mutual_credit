<?php

/**
 * @file
 *  Contains Drupal\mcapi\Plugin\Transitions\Unerase
 *
 */

namespace Drupal\mcapi\Plugin\Action;

use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Changes the transaction state to what it was before it was erased.
 *
 * @Action(
 *   id = "mcapi_transaction.unerase_action",
 *   label = @Translation("Unerase a transaction"),
 *   type = "mcapi_transaction",
 *   confirm_form_route_name = "mcapi.transaction.operation"
 * )
 */
class Unerase extends \Drupal\mcapi\Plugin\TransactionActionBase {
    
  private $keyValue;
  
  function __construct(array $configuration, $plugin_id, array $plugin_definition, $entity_form_builder, $module_handler, $relative_active_plugins, $entity_type_manager, $entity_display_respository, $key_value) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $entity_form_builder, $module_handler, $relative_active_plugins, $entity_type_manager, $entity_display_respository);
    $this->keyValue = $key_value;
  }
  
  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration, 
      $plugin_id,
      $plugin_definition,
      $container->get('entity.form_builder'),
      $container->get('module_handler'),
      $container->get('mcapi.transaction_relative_manager')->activePlugins(),
      $container->get('entity_type.manager'),
      $container->get('entity_display.repository'),
      $container->get('keyvalue.database')
    );
  }
  
  /**
   * {@inheritdoc}
  */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $elements = parent::buildConfigurationForm($form, $form_state);
    $elements['states'] = [
      '#type' => 'value',
      '#value' => ['erased' => 'erased']
    ];
    return $elements;
  }
  
  /**
   * {@inheritdoc}
  */
  public function execute($object = NULL) {
    $store = $this->keyValue->get('mcapi_erased');
    $object->set('state', $store->get($object->serial->value, 'done'));
    $store->delete($object->serial->value);
  }

}
