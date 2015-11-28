<?php

/**
 * @file
 *  Contains Drupal\mcapi\Plugin\Transitions\Erase
 * 
 * @todo this plugin could have an option to erase either by changing the 
 * transaction state OR by adding a reversal transaction to the cluster. It must
 * be either/or because the two mechanisms cannot happen to different currencies 
 * in the same transaction
 *
 */

namespace Drupal\mcapi\Plugin\Action;

use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Changes the transaction state to 'erased'.
 *
 * @Action(
 *   id = "mcapi_transaction.erase_action",
 *   label = @Translation("Erase a transaction"),
 *   type = "mcapi_transaction",
 *   confirm_form_route_name = "mcapi.transaction.operation"
 * )
 */
class Erase extends \Drupal\mcapi\Plugin\TransactionActionBase implements \Drupal\Core\Plugin\ContainerFactoryPluginInterface{
    
  private $keyValue;
  
  function __construct($configuration, $plugin_id, $plugin_definition, $entity_form_builder, $module_handler, $relative_active_plugins, $entity_type_manager, $entity_display_respository, $key_value) {
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
    $elements['states'][TRANSACTION_STATE_ERASED] = [
      '#disabled' => TRUE,//setting #default value seems to have no effect
    ];
    return $elements;
  }
  
  /**
   * {@inheritdoc}
   */
  public function execute($object = NULL) {
    //keep a separate record of the previous state of erased transactions, so they can be unerased
    $key_value_store = $this->keyValue
      ->get('mcapi_erased')
      ->set($object->serial->value, $object->state->target_id);

    $saved = $object
      ->set('state', TRANSACTION_STATE_ERASED);//will be saved later

  }
  

}
