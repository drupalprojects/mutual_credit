<?php

namespace Drupal\mcapi\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Drupal\mcapi\Mcapi;

/**
 * Because the transaction collection is also the field ui base route, and
 * because views provides a superior listing to the entity's official
 * list_builder, this alters that view's route to comply with the entity.
 */
class MigrationSubscriber implements EventSubscriberInterface {

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [
      'migrate.pre_row_save' => [['migratePreRowSave']]
    ];
  }

  /**
   * @param Drupal\migrate\Event\MigratePreRowSave $event
   *
   * Change the name of the transaction description field, which was a vairable
   * in d7, but an entity basefield in d8. Change the names of the transaction
   * entitytype and bundle
   */
  public function migratePreRowSave($event) {
    $row = $event->getRow();
    $migration = $event->getMigration();

    // For some reason each migration is running with 2 names, with and without
    // the upgrade_prefix.
    if (substr($migration->id(), 0, 8) == 'upgrade_') {
      $migration_id = substr($migration->id(), 8);
    }
    else {
      $migration_id = $migration->id();
    }

    // Change the name of the transaction entity and bundle.
    if ($migration->id() == 'd7_field' or $migration->id() == 'd7_field_instance') {
      if ($row->getDestinationProperty('entity_type') == 'transaction') {
        $row->setDestinationProperty('entity_type', 'mcapi_transaction');
        if ($row->getSourceProperty('plugin') == 'd7_field_instance') {
          $row->setDestinationProperty('bundle', 'mcapi_transaction');
        }
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
    // Horrible but necessary if we change the entity name
    if ($migration->getDestinationConfiguration()['plugin'] == 'component_entity_display') {
      if ($row->getDestinationProperty('entity_type') == 'transaction') {
        $row->setDestinationProperty('entity_type', 'mcapi_transaction');
        $row->setDestinationProperty('bundle', 'mcapi_transaction');
      }
    }

    if ($event->getMigration()->id() == 'd7_mcapi_form') {
      if ($row->getSourceProperty('name') == '1stparty') {
        $direction = $row->getSourceProperty('direction');
        if ($direction->preset == 'outgoing') {
          $row->setDestinationProperty('mode',  'credit');
        }
        elseif ($direction->preset == 'incoming') {
          $row->setDestinationProperty('mode',  'bill');
        }
      }
    }
  }


}
