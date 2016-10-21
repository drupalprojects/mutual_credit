<?php

namespace Drupal\mcapi\Plugin\Action;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\mcapi\Plugin\TransactionActionBase;
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
class Erase extends TransactionActionBase implements ContainerFactoryPluginInterface {

  private $keyValue;

  /**
   * Constructor.
   */
  public function __construct($configuration, $plugin_id, $plugin_definition, $entity_form_builder, $module_handler, $relative_active_plugins, $entity_type_manager, $entity_display_respository, $current_user, $key_value) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $entity_form_builder, $module_handler, $relative_active_plugins, $entity_type_manager, $entity_display_respository, $current_user);
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
      $container->get('mcapi.transaction_relative_manager'),
      $container->get('entity_type.manager'),
      $container->get('entity_display.repository'),
      $container->get('current_user'),
      $container->get('keyvalue.database')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $elements = parent::buildConfigurationForm($form, $form_state);
    $elements['states']['erased'] = [
    // Setting #default value seems to have no effect.
      '#disabled' => TRUE,
    ];
    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function execute($object = NULL) {
    // Keep a separate record of the previous state of erased transactions, so
    // they can be unerased.
    $this->keyValue
      ->get('mcapi_erased')
      ->set($object->serial->value, $object->state->target_id);

    // Will be saved later.
    $object->set('state', 'erased');
    parent::execute($object);

  }

}
