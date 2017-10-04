<?php

namespace Drupal\mcapi\EventSubscriber;

use Drupal\mcapi\Mcapi;
use Drupal\mcapi\Event\McapiEvents;
use Drupal\mcapi\Event\TransactionSaveEvents;
use Drupal\mcapi\Event\TransactionAssembleEvent;
use Drupal\Component\EventDispatcher\ContainerAwareEventDispatcher;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Hooks for saving a transaction.
 */
class TransactionEventSubscriber implements EventSubscriberInterface {

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [
      McapiEvents::ASSEMBLE => ['onmakeChildren'],
      McapiEvents::ACTION => ['onTransactionAction'],
      'migrate.pre_row_save' => [['preImportFieldConfig']]
    ];
  }

  /**
   * This is an example for now, but it mightMcapi work with rules later on.
   *
   * Use $events->addChild($transaction).
   *
   * @param TransactionAssembleEvent $event
   *   No ->arguments() are passed. getSubject() gives the transaction.
   * @param string $eventName
   *   Which is always mcapi_transaction.assemble.
   * @param ContainerAwareEventDispatcher $container
   *   The container.
   */
  public function onMakeChildren(TransactionAssembleEvent $event, $eventName, ContainerAwareEventDispatcher $container) {
    // drupal_set_message('onmakechildren: '.$eventName);//testing.
  }

  /**
   * Acts on a transaction and returns a render array in $events->output.
   *
   * @param TransactionSaveEvents $events
   *   $events->getArguments() yields form_values, old_state and operation name,
   *    or action. $events->getSubject() gives the transaction.
   * @param string $eventName
   *   The machine name of the event.
   * @param ContainerAwareEventDispatcher $container
   *   The container.
   */
  public function onTransactionAction(TransactionSaveEvents $events, $eventName, ContainerAwareEventDispatcher $container) {
    // $events->setMessage('onTransactionAction: '.$eventName);.
  }

  /**
   * @param Drupal\migrate\Event\MigratePreRowSave $event
   *
   * Change the name of the transaction description field, which was a vairable
   * in d7, but an entity basefield in d8. Change the names of the transaction
   * entitytype and bundle
   */
  public function preImportFieldConfig($event) {
    $row = $event->getRow();
    $migration = $event->getMigration();
    // Change the name of the transaction entity and bundle.
    if ($migration->id() == 'd7_field' or $migration->id() == 'd7_field_instance') {
      if ($row->getDestinationProperty('entity_type') == 'transaction') {
        $row->setDestinationProperty('entity_type', 'mcapi_transaction');
        if ($row->getSourceProperty('plugin') == 'd7_field_instance') {
          $row->setDestinationProperty('bundle', 'mcapi_transaction');
        }
        echo "\nchanging transaction entitytype for field : ".$row->getSourceProperty('field_name');
      }
    }

    // Rename the transaction description field
    if ($migration->id() == 'd7_mcapi_transaction') {
      $old_desc_field_name = Mcapi::d7_description_fieldname($migration);
      // Rename the transaction description field
      $row->setDestinationProperty(
        'description',
        $row->getSourceProperty($old_desc_field_name)
      );
      $row->removeDestinationProperty($old_desc_field_name);
    }
  }

}
